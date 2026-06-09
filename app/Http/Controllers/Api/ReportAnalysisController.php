<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeFeedbackJob;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportAnalysisController extends Controller
{
    public function analyze(Request $request, Report $report): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'model' => ['nullable', 'string'],
            'batch_size' => ['nullable', 'integer', 'min:1', 'max:200'],
            'feedback_ids' => ['nullable', 'array'],
            'feedback_ids.*' => ['integer'],
        ]);

        $report->ai_status = 'pending';
        $report->ai_error = null;
        $report->ai_model = $data['model'] ?? $report->ai_model ?? config('services.openrouter.model');
        $report->save();

        $options = [
            'model' => $report->ai_model,
            'batch_size' => $data['batch_size'] ?? 25,
        ];

        if (!empty($data['feedback_ids'])) {
            $options['feedback_ids'] = $data['feedback_ids'];
        }

        AnalyzeFeedbackJob::dispatch($report->id, $options);

        return response()->json(['message' => 'Analysis queued', 'report_id' => $report->id, 'ai_status' => 'pending'], 201);
    }

    public function status(Request $request, Report $report): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'report_id' => $report->id,
            'ai_status' => $report->ai_status ?? null,
            'ai_started_at' => optional($report->ai_started_at)->toDateTimeString(),
            'ai_completed_at' => optional($report->ai_completed_at)->toDateTimeString(),
            'ai_error' => $report->ai_error ?? null,
            'ai_model' => $report->ai_model ?? null,
            'ai_analysis' => $report->ai_analysis ?? null,
        ]);
    }
}

