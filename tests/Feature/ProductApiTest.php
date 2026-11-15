<?php

namespace Tests\Feature;

use App\Models\Meal;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Testy integracyjne REST API: produkty, role (authorization), paginacja i wyszukiwanie.
 */
class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_are_paginated_and_searchable(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Product::factory()->count(12)->create();
        Product::factory()->create(['name' => 'Grillowana pierś z kurczaka']);

        $this->getJson('/api/products?per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 13)
            ->assertJsonPath('meta.last_page', 3);

        $this->getJson('/api/products?q=KURCZ')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Grillowana pierś z kurczaka');
    }

    public function test_regular_user_cannot_create_product(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/products', ['name' => 'Tofu', 'kcal_100g' => 76])
            ->assertForbidden();

        $this->assertDatabaseMissing('products', ['name' => 'Tofu']);
    }

    public function test_admin_can_create_product_and_validation_works(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/products', ['name' => '', 'kcal_100g' => -5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'kcal_100g']);

        $this->postJson('/api/products', ['name' => 'Tofu', 'category' => 'Białko', 'kcal_100g' => 76])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Tofu')
            ->assertJsonPath('data.per_100g.kcal', 76);
    }

    public function test_product_used_in_meal_cannot_be_deleted(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $product = Product::factory()->create();
        $meal = Meal::create([
            'user_id' => User::factory()->create()->id,
            'meal_date' => today(),
            'meal_type' => 'lunch',
        ]);
        $meal->items()->create(['product_id' => $product->id, 'grams' => 100]);

        $this->deleteJson("/api/products/{$product->id}")
            ->assertConflict()
            ->assertJsonPath('message', 'Nie można usunąć produktu, który jest użyty w zapisanych posiłkach.');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }
}
