<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Meal;
use App\Models\Product;
use App\Models\ShoppingItem;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Dane demo przeniesione ze starego projektu (WdPAI database.sql).
 * Wszystkie konta demo mają hasło "password".
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // --- Użytkownicy -------------------------------------------------
        $admin = User::factory()->admin()->create([
            'name' => 'admin', 'email' => 'admin@mealcore.com', 'daily_calorie_goal' => 2250,
        ]);
        $alex = User::factory()->create([
            'name' => 'alex', 'email' => 'alex@mealcore.com', 'daily_calorie_goal' => 2250,
        ]);
        $sarah = User::factory()->create([
            'name' => 'sarah', 'email' => 'sarah@mealcore.com', 'daily_calorie_goal' => 1800,
        ]);
        $mike = User::factory()->create([
            'name' => 'mike', 'email' => 'mike@mealcore.com', 'daily_calorie_goal' => 2400,
        ]);
        $emma = User::factory()->create([
            'name' => 'emma', 'email' => 'emma@mealcore.com', 'daily_calorie_goal' => 2000,
        ]);

        // --- Grupy + członkostwa ----------------------------------------
        $family = Group::create(['name' => 'The Miller Family', 'type' => 'family']);
        $roommates = Group::create(['name' => 'Roommates', 'type' => 'roommates']);
        $office = Group::create(['name' => 'Office Fridge', 'type' => 'office']);

        $family->members()->attach([
            $admin->id => ['member_role' => Group::ROLE_OWNER],
            $alex->id => ['member_role' => Group::ROLE_MEMBER],
            $sarah->id => ['member_role' => Group::ROLE_MEMBER],
        ]);
        $roommates->members()->attach([
            $admin->id => ['member_role' => Group::ROLE_OWNER],
            $mike->id => ['member_role' => Group::ROLE_MEMBER],
            $emma->id => ['member_role' => Group::ROLE_MEMBER],
        ]);
        $office->members()->attach([
            $mike->id => ['member_role' => Group::ROLE_OWNER],
            $sarah->id => ['member_role' => Group::ROLE_MEMBER],
            $emma->id => ['member_role' => Group::ROLE_MEMBER],
        ]);

        // --- Produkty (wartości na 100 g) -------------------------------
        $products = [
            ['Sourdough Avocado Toast', 'Bakery', 124, 5.6, 12.8, 6.4],
            ['Double Espresso', 'Other', 8, 0.2, 1.0, 0.1],
            ['Salmon Power Bowl', 'Protein', 162, 8.1, 12.4, 7.9],
            ['Blueberry Greek Yogurt', 'Dairy', 123, 8.0, 12.0, 3.5],
            ['Grilled Chicken Breast', 'Protein', 165, 31.0, 0.0, 3.6],
            ['Fresh Baby Spinach', 'Produce', 23, 2.9, 3.6, 0.4],
            ['Organic Quinoa', 'Other', 120, 4.4, 21.3, 1.9],
            ['Whole Milk', 'Dairy', 61, 3.2, 4.8, 3.3],
            ['Artisan Rye Bread', 'Bakery', 259, 8.5, 48.3, 3.3],
            ['Avocado', 'Produce', 160, 2.0, 8.5, 14.7],
            ['Banana', 'Produce', 89, 1.1, 22.8, 0.3],
            ['Brown Rice (cooked)', 'Other', 112, 2.6, 23.5, 0.9],
            ['Almonds', 'Other', 579, 21.2, 21.6, 49.9],
            ['Eggs', 'Protein', 155, 13.0, 1.1, 11.0],
            ['Sweet Potato', 'Produce', 86, 1.6, 20.1, 0.1],
            ['Cheddar Cheese', 'Dairy', 403, 25.0, 1.3, 33.0],
            ['Oatmeal', 'Other', 68, 2.4, 12.0, 1.4],
            ['Broccoli', 'Produce', 34, 2.8, 6.6, 0.4],
            ['Tuna (canned)', 'Protein', 116, 25.5, 0.0, 0.8],
            ['Peanut Butter', 'Other', 588, 25.0, 20.0, 50.0],
        ];

        $p = [];
        foreach ($products as [$name, $category, $kcal, $protein, $carbs, $fat]) {
            $p[$name] = Product::create([
                'name' => $name,
                'category' => $category,
                'kcal_100g' => $kcal,
                'protein_100g' => $protein,
                'carbs_100g' => $carbs,
                'fat_100g' => $fat,
            ]);
        }

        // --- Posiłki demo dla alexa (dzisiaj) ---------------------------
        $meals = [
            ['breakfast', 'Breakfast', [['Sourdough Avocado Toast', 250], ['Double Espresso', 60]]],
            ['lunch', 'Lunch', [['Salmon Power Bowl', 420]]],
            ['snack', 'Snacks', [['Blueberry Greek Yogurt', 150]]],
        ];

        foreach ($meals as [$type, $name, $items]) {
            $meal = Meal::create([
                'user_id' => $alex->id,
                'meal_date' => today(),
                'meal_type' => $type,
                'name' => $name,
            ]);

            foreach ($items as [$productName, $grams]) {
                $meal->items()->create([
                    'product_id' => $p[$productName]->id,
                    'grams' => $grams,
                ]);
            }
        }

        // --- Lista zakupów rodziny --------------------------------------
        $shopping = [
            [$alex, 'Fresh Organic Spinach', 'Produce', '1 bag', false],
            [$admin, 'Greek Yogurt (Large)', 'Dairy', '1 tub', false],
            [$alex, 'Artisan Rye Bread', 'Bakery', '1 loaf', false],
            [$admin, 'Avocados (3pk)', 'Produce', '1 pack', true],
            [$alex, 'Chicken Breast', 'Protein', '500 g', true],
        ];

        foreach ($shopping as [$user, $productName, $category, $quantity, $checked]) {
            ShoppingItem::create([
                'group_id' => $family->id,
                'added_by' => $user->id,
                'product_name' => $productName,
                'category' => $category,
                'quantity' => $quantity,
                'is_checked' => $checked,
                'bought_at' => $checked ? now() : null,
            ]);
        }
    }
}
