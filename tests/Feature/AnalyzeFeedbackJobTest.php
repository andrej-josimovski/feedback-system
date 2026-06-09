<?php

namespace Tests\Feature;

use App\Jobs\AnalyzeFeedbackJob;
use App\Models\Feedback;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AnalyzeFeedbackJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_summarizes_product_feedback_into_report_sections(): void
    {
        [$productId, $userId] = $this->createProductAndUser();

        $report = Report::create([
            'product_id' => $productId,
            'user_id' => $userId,
            'title' => 'June Feedback',
            'period_from' => '2026-06-01',
            'period_to' => '2026-06-30',
        ]);

        $firstFeedback = Feedback::create([
            'product_id' => $productId,
            'user_id' => $userId,
            'rating' => 2,
            'comment' => 'The checkout page is slow and sometimes times out.',
            'created_at' => '2026-06-05 10:00:00',
            'updated_at' => '2026-06-05 10:00:00',
        ]);

        Feedback::create([
            'product_id' => $productId,
            'user_id' => $userId,
            'rating' => 5,
            'comment' => 'The new product filters are very helpful.',
            'created_at' => '2026-06-06 10:00:00',
            'updated_at' => '2026-06-06 10:00:00',
        ]);

        Http::fake([
            '*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'clusters' => [
                                    [
                                        'cluster_id' => 'checkout',
                                        'theme' => 'Checkout performance',
                                        'members' => [$firstFeedback->id],
                                        'ai_summary' => 'Customers report checkout delays and timeouts, reflected by low ratings on affected feedback.',
                                        'issues' => ['Checkout is slow'],
                                        'proposals' => ['Improve checkout response time'],
                                        'combined_text' => 'Checkout page is slow and sometimes times out.',
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        (new AnalyzeFeedbackJob($report->id))->handle();

        $report->refresh();

        $this->assertSame('completed', $report->ai_status);
        $this->assertSame(2, $report->ai_analysis['feedback_count']);
        $this->assertSame(3.5, $report->ai_analysis['average_rating']);
        $this->assertStringContainsString('average rating of 3.5/5', $report->ai_analysis['summary_message']);
        $this->assertDatabaseHas('report_sections', [
            'report_id' => $report->id,
            'theme' => 'Checkout performance',
            'ai_summary' => 'Customers report checkout delays and timeouts, reflected by low ratings on affected feedback.',
        ]);

        Http::assertSent(function ($request) use ($firstFeedback): bool {
            $content = $request['messages'][1]['content'];

            return str_contains($content, '"rating":2')
                && str_contains($content, 'checkout page is slow')
                && str_contains($content, (string) $firstFeedback->id);
        });
    }

    /**
     * @return array{int, int}
     */
    private function createProductAndUser(): array
    {
        $teamId = DB::table('teams')->insertGetId([
            'name' => 'AI Report Team',
            'slug' => 'ai-report-team',
            'description' => 'Team used for AI report tests.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'team_id' => $teamId,
            'name' => 'AI Test Product',
            'slug' => 'ai-test-product',
            'description' => 'Product used in AI tests.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = User::factory()->create([
            'team_id' => $teamId,
        ])->id;

        return [$productId, $userId];
    }
}
