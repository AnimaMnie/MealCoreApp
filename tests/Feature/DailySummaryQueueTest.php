<?php

namespace Tests\Feature;

use App\Events\MealLogChanged;
use App\Listeners\UpdateDailySummary;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Mechanizm asynchroniczny: event MealLogChanged + listener w kolejce.
 */
class DailySummaryQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_logging_meal_dispatches_event_handled_by_queued_listener(): void
    {
        Event::fake([MealLogChanged::class]);
        Sanctum::actingAs($user = User::factory()->create());
        $product = Product::factory()->create();

        $this->postJson('/api/meals', [
            'meal_date' => today()->toDateString(),
            'meal_type' => 'lunch',
            'items' => [['product_id' => $product->id, 'grams' => 100]],
        ])->assertCreated();

        Event::assertDispatched(MealLogChanged::class, fn ($e) => $e->userId === $user->id
            && $e->date === today()->toDateString());
        Event::assertListening(MealLogChanged::class, UpdateDailySummary::class);
        $this->assertContains(ShouldQueue::class, class_implements(UpdateDailySummary::class));
    }

    public function test_queue_job_stores_daily_summary_used_by_history(): void
    {
        // W testach kolejka działa synchronicznie (QUEUE_CONNECTION=sync w phpunit.xml).
        $user = User::factory()->create(['daily_calorie_goal' => 500]);
        Sanctum::actingAs($user);
        $product = Product::factory()->create(['kcal_100g' => 300]);

        $this->postJson('/api/meals', [
            'meal_date' => today()->toDateString(),
            'meal_type' => 'dinner',
            'items' => [['product_id' => $product->id, 'grams' => 200]], // 600 kcal > cel 500
        ])->assertCreated();

        $this->assertDatabaseHas('daily_summaries', [
            'user_id' => $user->id,
            'kcal' => 600,
            'goal_exceeded' => true,
        ]);

        $this->getJson('/api/meals/history?days=7')
            ->assertOk()
            ->assertJsonCount(7, 'data.days')
            ->assertJsonPath('data.days.6.kcal', 600)
            ->assertJsonPath('data.days_goal_exceeded', 1);
    }
}
