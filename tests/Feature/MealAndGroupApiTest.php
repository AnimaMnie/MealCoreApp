<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Testy integracyjne REST API: dziennik posiłków, grupy i lista zakupów.
 */
class MealAndGroupApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_logs_meal_and_gets_calculated_totals(): void
    {
        $user = User::factory()->create(['daily_calorie_goal' => 2000]);
        Sanctum::actingAs($user);
        $chicken = Product::factory()->create(['kcal_100g' => 165, 'protein_100g' => 31]);

        $this->postJson('/api/meals', [
            'meal_date' => today()->toDateString(),
            'meal_type' => 'lunch',
            'items' => [['product_id' => $chicken->id, 'grams' => 200]],
        ])
            ->assertCreated()
            ->assertJsonPath('data.totals.kcal', 330)
            ->assertJsonPath('data.totals.protein', 62);

        $this->getJson('/api/meals/summary')
            ->assertOk()
            ->assertJsonPath('data.totals.kcal', 330)
            ->assertJsonPath('data.remaining_kcal', 1670);
    }

    public function test_user_cannot_see_someone_elses_meal(): void
    {
        $owner = User::factory()->create();
        $meal = $owner->meals()->create(['meal_date' => today(), 'meal_type' => 'breakfast']);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/meals/{$meal->id}")->assertForbidden();
        $this->deleteJson("/api/meals/{$meal->id}")->assertForbidden();
    }

    public function test_group_name_must_be_unique(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Group::create(['name' => 'Rodzina Kowalskich']);

        $this->postJson('/api/groups', ['name' => 'rodzina kowalskich'])
            ->assertConflict()
            ->assertJsonPath('message', 'Grupa o takiej nazwie już istnieje.');
    }

    public function test_only_group_members_can_use_shopping_list(): void
    {
        $member = User::factory()->create();
        $outsider = User::factory()->create();
        $group = Group::create(['name' => 'Współlokatorzy']);
        $group->members()->attach($member->id, ['member_role' => Group::ROLE_OWNER]);

        Sanctum::actingAs($outsider);
        $this->getJson("/api/groups/{$group->id}/shopping-items")->assertForbidden();
        $this->postJson("/api/groups/{$group->id}/shopping-items", ['product_name' => 'Mleko'])->assertForbidden();

        Sanctum::actingAs($member);
        $this->postJson("/api/groups/{$group->id}/shopping-items", ['product_name' => 'Mleko'])
            ->assertCreated()
            ->assertJsonPath('data.added_by', $member->name);
        $this->getJson("/api/groups/{$group->id}/shopping-items")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
