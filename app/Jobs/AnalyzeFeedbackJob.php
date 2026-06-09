<?php

namespace App\Jobs;

use App\Models\Feedback;
use App\Models\Report;
use App\Models\ReportSection;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AnalyzeFeedbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    protected int $reportId;

    protected array $options;

    /**
     * Create a new job instance.
     *
     * @param int $reportId
     * @param array $options
     */
    public function __construct(int $reportId, array $options = [])
    {
        $this->reportId = $reportId;
        $this->options = $options;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $report = Report::find($this->reportId);
        if (!$report) {
            Log::warning("AnalyzeFeedbackJob: report {$this->reportId} not found");
            return;
        }

        $report->ai_status = 'running';
        $report->ai_started_at = now();
        $report->ai_model = $this->options['model'] ?? config('services.openrouter.model');
        $report->save();

        try {
            // Fetch feedbacks for this report's product (or provided ids)
            if (!empty($this->options['feedback_ids']) && is_array($this->options['feedback_ids'])) {
                $feedbacks = Feedback::where('product_id', $report->product_id)
                    ->whereIn('id', $this->options['feedback_ids'])
                    ->get();
            } else {
                $feedbacks = Feedback::where('product_id', $report->product_id)
                    ->whereBetween('created_at', [
                        $report->period_from->startOfDay(),
                        $report->period_to->endOfDay(),
                    ])
                    ->get();
            }

            $feedbackItems = $feedbacks
                ->filter(fn (Feedback $feedback): bool => filled($feedback->comment))
                ->mapWithKeys(function (Feedback $feedback): array {
                    return [
                        $feedback->id => [
                            'rating' => $feedback->rating,
                            'comment' => trim((string) $feedback->comment),
                        ],
                    ];
                })
                ->toArray();

            if (empty($feedbackItems)) {
                $report->ai_status = 'completed';
                $report->ai_analysis = [
                    'summary_message' => 'No written feedback was submitted for this product during the report period.',
                    'clusters' => [],
                    'meta' => ['note' => 'no_feedbacks'],
                ];
                $report->ai_completed_at = now();
                $report->save();
                return;
            }

            $batchSize = (int)($this->options['batch_size'] ?? 25);
            $batches = array_chunk($feedbackItems, $batchSize, true);

            $allClusters = [];
            $batchIndex = 0;

            foreach ($batches as $batch) {
                $batchIndex++;
                $prompt = $this->buildClusteringPrompt($batch);
                $response = $this->callOpenRouter($prompt);

                if (!$response) {
                    throw new Exception("Empty response from OpenRouter for report {$report->id} batch {$batchIndex}");
                }

                $parsed = $this->parseApiResponse($response);
                if (isset($parsed['clusters']) && is_array($parsed['clusters'])) {
                    $allClusters = array_merge($allClusters, $parsed['clusters']);
                }
            }

            // Optional: a simple merge step to deduplicate cluster themes by title
            $merged = $this->mergeClusters($allClusters);
            $summaryMessage = $this->buildReportSummaryMessage($feedbacks->all(), $merged);

            // Store as report_sections and ai_analysis
            $this->storeAnalysisIntoReport($report, $merged);

            $report->ai_status = 'completed';
            $report->ai_completed_at = now();
            $report->ai_analysis = [
                'summary_message' => $summaryMessage,
                'feedback_count' => $feedbacks->count(),
                'average_rating' => round((float) $feedbacks->avg('rating'), 2),
                'clusters' => $merged,
                'meta' => [
                    'model' => $report->ai_model,
                    'provider' => config('services.openrouter.base_uri'),
                ],
            ];
            $report->save();

        } catch (Exception $e) {
            Log::error('AnalyzeFeedbackJob failed', ['report_id' => $this->reportId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $report->ai_status = 'failed';
            $report->ai_error = $e->getMessage();
            $report->ai_completed_at = now();
            $report->save();
            // rethrow so queue can handle retries if desired
            throw $e;
        }
    }

    protected function buildClusteringPrompt(array $batch): array
    {
        // Build a clear instruction and include the batch as JSON
        $items = [];
        foreach ($batch as $id => $feedback) {
            $items[] = [
                'id' => (int)$id,
                'rating' => (int) $feedback['rating'],
                'comment' => $feedback['comment'],
            ];
        }

        $instruction = "You are an assistant. Given an array of product feedback items with ratings and comments, group them into clusters by topic and summarize each cluster for a product report. Return strictly valid JSON with the following schema:\n{\n  \"clusters\": [\n    {\n      \"cluster_id\": \"string\",\n      \"theme\": \"short theme title\",\n      \"members\": [list of feedback ids],\n      \"ai_summary\": \"2-4 sentence report-ready summary of what customers said, including rating context\",\n      \"issues\": [\"short issue or pain point\"],\n      \"proposals\": [\"short improvement proposal\"],\n      \"combined_text\": \"brief evidence notes from comments, not every full comment\"\n    }\n  ]\n}\nDo not include any text outside the JSON object. Keep themes short (3-6 words). Keep summaries factual and do not invent details.";

        $payload = [
            'instruction' => $instruction,
            'items' => $items,
            'model' => $this->options['model'] ?? config('services.openrouter.model'),
        ];

        return $payload;
    }

    protected function callOpenRouter(array $payload): ?array
    {
        $base = rtrim(config('services.openrouter.base_uri', 'https://api.openrouter.ai'), '/');
        $url = $base . '/v1/chat/completions';

        $apiKey = config('services.openrouter.key');
        $model = $payload['model'] ?? config('services.openrouter.model');

        $system = "You are a JSON-only assistant specialized in summarizing product feedback comments and ratings for business reports. Only output valid JSON as requested.";

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $payload['instruction'] . "\n\n" . json_encode($payload['items'], JSON_UNESCAPED_UNICODE)],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])
                ->timeout((int)config('services.openrouter.timeout', 60))
                ->post($url, [
                    'model' => $model,
                    'messages' => $messages,
                    'max_tokens' => 1500,
                    'temperature' => 0.0,
                ]);

            if ($response->failed()) {
                Log::warning('OpenRouter API returned non-200', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $body = $response->json();

            // Typical OpenRouter/OpenAI chat responses: choices[0].message.content
            if (isset($body['choices'][0]['message']['content'])) {
                $content = $body['choices'][0]['message']['content'];
                // Try to parse JSON substring (in case model included backticks or text)
                $json = $this->extractJson($content);
                if ($json !== null) {
                    return $json;
                }
            }

            return null;

        } catch (Exception $e) {
            Log::error('OpenRouter call failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    protected function extractJson(string $text): ?array
    {
        // Try to find the first JSON object/array in the text
        $start = strpos($text, '{');
        if ($start === false) {
            $start = strpos($text, '[');
        }
        if ($start === false) {
            return null;
        }
        $substr = substr($text, $start);

        // Attempt progressive parsing trimming until valid JSON
        $trimmed = rtrim($substr);
        while (!empty($trimmed)) {
            try {
                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            } catch (Exception $e) {
                // continue trimming
            }
            // remove last character and retry
            $trimmed = substr($trimmed, 0, -1);
        }

        return null;
    }

    protected function parseApiResponse(array $response): array
    {
        // Expecting ['clusters' => [ ... ]]
        if (isset($response['clusters']) && is_array($response['clusters'])) {
            return ['clusters' => $response['clusters']];
        }

        // Some wrappers may nest the result
        if (isset($response['result']) && isset($response['result']['clusters'])) {
            return ['clusters' => $response['result']['clusters']];
        }

        // Fallback empty
        return ['clusters' => []];
    }

    protected function mergeClusters(array $clusters): array
    {
        $map = [];
        foreach ($clusters as $c) {
            $theme = isset($c['theme']) ? Str::lower(trim($c['theme'])) : null;
            if (!$theme) {
                // generate a key
                $theme = 'cluster_' . Str::random(6);
            }
            if (!isset($map[$theme])) {
                $map[$theme] = $c;
                // ensure cluster_id exists
                if (empty($map[$theme]['cluster_id'])) {
                    $map[$theme]['cluster_id'] = 'c_' . Str::random(8);
                }
            } else {
                // merge members and narrative fields
                $map[$theme]['members'] = array_values(array_unique(array_merge($map[$theme]['members'] ?? [], $c['members'] ?? [])));
                $map[$theme]['combined_text'] = trim(($map[$theme]['combined_text'] ?? '') . "\n" . ($c['combined_text'] ?? ''));
                $map[$theme]['ai_summary'] = trim(($map[$theme]['ai_summary'] ?? '') . "\n" . ($c['ai_summary'] ?? ''));
                $map[$theme]['issues'] = array_values(array_unique(array_merge($map[$theme]['issues'] ?? [], $c['issues'] ?? [])));
                $map[$theme]['proposals'] = array_values(array_unique(array_merge($map[$theme]['proposals'] ?? [], $c['proposals'] ?? [])));
            }
        }

        return array_values($map);
    }

    /**
     * @param list<Feedback> $feedbacks
     */
    protected function buildReportSummaryMessage(array $feedbacks, array $clusters): string
    {
        $feedbackCount = count($feedbacks);
        $averageRating = $feedbackCount > 0
            ? round(array_sum(array_map(fn (Feedback $feedback): int => $feedback->rating, $feedbacks)) / $feedbackCount, 2)
            : 0;

        $themes = collect($clusters)
            ->take(3)
            ->map(fn (array $cluster): string => $cluster['theme'] ?? 'General feedback')
            ->implode(', ');

        $message = "This report summarizes {$feedbackCount} feedback message";
        $message .= $feedbackCount === 1 ? '' : 's';
        $message .= " with an average rating of {$averageRating}/5.";

        if ($themes !== '') {
            $message .= " The main themes are {$themes}.";
        }

        return $message;
    }

    protected function storeAnalysisIntoReport(Report $report, array $clusters): void
    {
        // For each cluster, create or update a ReportSection with ai_summary placeholder
        foreach ($clusters as $idx => $cluster) {
            $theme = $cluster['theme'] ?? ('Cluster ' . ($idx + 1));
            $section = $report->sections()->firstOrNew(['theme' => $theme]);
            $section->order = $section->order ?? ($idx + 1);
            $section->ai_summary = substr($cluster['ai_summary'] ?? $cluster['combined_text'] ?? '', 0, 2000);
            $section->issues = $cluster['issues'] ?? [];
            $section->proposals = $cluster['proposals'] ?? [];
            $section->save();
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        try {
            $report = Report::find($this->reportId);
            if ($report) {
                $report->ai_status = 'failed';
                $report->ai_error = $exception->getMessage();
                $report->ai_completed_at = now();
                $report->save();
            }
        } catch (Exception $e) {
            Log::error('Failed to mark report as failed in AnalyzeFeedbackJob::failed', ['error' => $e->getMessage()]);
        }

        Log::error('AnalyzeFeedbackJob final failure', ['report_id' => $this->reportId, 'error' => $exception->getMessage()]);
    }
}
