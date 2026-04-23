<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_gets_pending_review_status_on_creation(): void
    {
        [$productId, $userId] = $this->createProductAndUser();

        $response = $this->postJson('/api/reports', [
            'product_id' => $productId,
            'user_id' => $userId,
            'title' => 'New Report Q2 2026',
            'period_from' => '2026-04-01',
            'period_to' => '2026-04-30',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('status', 'pending_review')
            ->assertJsonPath('title', 'New Report Q2 2026');

        $this->assertDatabaseHas('reports', [
            'product_id' => $productId,
            'status' => 'pending_review',
            'title' => 'New Report Q2 2026',
        ]);
    }

    public function test_report_validates_input(): void
    {
        $invalidDates = $this->postJson('/api/reports', [
            'product_id' => 999,
            'title' => 'Test',
            'period_from' => '2026-04-30',
            'period_to' => '2026-04-01', // before from
        ]);

        $invalidDates->assertStatus(422);
    }

    /**
     * @return array{int, int}
     */
    private function createProductAndUser(): array
    {
        $teamId = DB::table('teams')->insertGetId([
            'name' => 'Report Test Team',
            'slug' => 'report-test-team',
            'description' => 'Team used for report tests.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'team_id' => $teamId,
            'name' => 'Test Product for Reports',
            'slug' => 'test-product-reports',
            'description' => 'Product used in report tests.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = User::factory()->create([
            'team_id' => $teamId,
        ])->id;

        return [$productId, $userId];
    }
}

