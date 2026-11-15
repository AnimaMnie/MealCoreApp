<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->word()),
            'category' => fake()->randomElement(Product::CATEGORIES),
            'kcal_100g' => fake()->numberBetween(20, 600),
            'protein_100g' => fake()->numberBetween(0, 30),
            'carbs_100g' => fake()->numberBetween(0, 60),
            'fat_100g' => fake()->numberBetween(0, 40),
        ];
    }
}
