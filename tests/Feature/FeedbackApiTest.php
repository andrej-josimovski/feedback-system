<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FeedbackApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_feedback_via_global_endpoint(): void
    {
        [$productId, $userId] = $this->createProductAndUser();

        $response = $this->postJson('/api/feedback', [
            'product_id' => $productId,
            'user_id' => $userId,
            'rating' => 5,
            'comment' => 'Great feature set and very easy to use.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('product_id', $productId)
            ->assertJsonPath('rating', 5)
            ->assertJsonPath('user.id', $userId);

        $this->assertDatabaseHas('feedback', [
            'product_id' => $productId,
            'user_id' => $userId,
            'rating' => 5,
        ]);
    }

    public function test_it_creates_feedback_via_nested_product_endpoint(): void
    {
        [$productId, $userId] = $this->createProductAndUser();

        $response = $this->postJson("/api/products/{$productId}/feedback", [
            'user_id' => $userId,
            'rating' => 2,
            'comment' => 'Notification delay is still noticeable.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('product.id', $productId)
            ->assertJsonPath('feedback.rating', 2)
            ->assertJsonPath('feedback.user.id', $userId);

        $this->assertDatabaseHas('feedback', [
            'product_id' => $productId,
            'user_id' => $userId,
            'rating' => 2,
        ]);
    }

    public function test_it_validates_feedback_rating_and_nested_product_payload(): void
    {
        [$productId, $userId] = $this->createProductAndUser();

        $invalidRating = $this->postJson('/api/feedback', [
            'product_id' => $productId,
            'user_id' => $userId,
            'rating' => 7,
        ]);

        $invalidRating->assertStatus(422)->assertJsonValidationErrors(['rating']);

        $prohibitedProductId = $this->postJson("/api/products/{$productId}/feedback", [
            'product_id' => $productId,
            'rating' => 4,
        ]);

        $prohibitedProductId->assertStatus(422)->assertJsonValidationErrors(['product_id']);
    }

    /**
     * @return array{int, int}
     */
    private function createProductAndUser(): array
    {
        $teamId = DB::table('teams')->insertGetId([
            'name' => 'QA Team',
            'slug' => 'qa-team',
            'description' => 'Team used in API tests.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'team_id' => $teamId,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'Product used in API tests.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = User::factory()->create([
            'team_id' => $teamId,
        ])->id;

        return [$productId, $userId];
    }
}

