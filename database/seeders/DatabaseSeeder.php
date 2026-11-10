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
        $family = Group::create(['name' => 'Rodzina Kowalskich', 'type' => 'family']);
        $roommates = Group::create(['name' => 'Współlokatorzy', 'type' => 'roommates']);
        $office = Group::create(['name' => 'Biurowa lodówka', 'type' => 'office']);

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
            ['Tost z awokado na zakwasie', 'Pieczywo', 124, 5.6, 12.8, 6.4],
            ['Podwójne espresso', 'Inne', 8, 0.2, 1.0, 0.1],
            ['Miska z łososiem', 'Białko', 162, 8.1, 12.4, 7.9],
            ['Jogurt grecki z borówkami', 'Nabiał', 123, 8.0, 12.0, 3.5],
            ['Grillowana pierś z kurczaka', 'Białko', 165, 31.0, 0.0, 3.6],
            ['Młody szpinak', 'Warzywa i owoce', 23, 2.9, 3.6, 0.4],
            ['Komosa ryżowa', 'Inne', 120, 4.4, 21.3, 1.9],
            ['Mleko 3,2%', 'Nabiał', 61, 3.2, 4.8, 3.3],
            ['Chleb żytni', 'Pieczywo', 259, 8.5, 48.3, 3.3],
            ['Awokado', 'Warzywa i owoce', 160, 2.0, 8.5, 14.7],
            ['Banan', 'Warzywa i owoce', 89, 1.1, 22.8, 0.3],
            ['Ryż brązowy (ugotowany)', 'Inne', 112, 2.6, 23.5, 0.9],
            ['Migdały', 'Inne', 579, 21.2, 21.6, 49.9],
            ['Jajka', 'Białko', 155, 13.0, 1.1, 11.0],
            ['Batat', 'Warzywa i owoce', 86, 1.6, 20.1, 0.1],
            ['Ser cheddar', 'Nabiał', 403, 25.0, 1.3, 33.0],
            ['Owsianka', 'Inne', 68, 2.4, 12.0, 1.4],
            ['Brokuł', 'Warzywa i owoce', 34, 2.8, 6.6, 0.4],
            ['Tuńczyk z puszki', 'Białko', 116, 25.5, 0.0, 0.8],
            ['Masło orzechowe', 'Inne', 588, 25.0, 20.0, 50.0],
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
        // meal_type to kod (breakfast, lunch...), a frontend wyświetli go po polsku.
        $meals = [
            ['breakfast', 'Śniadanie', [['Tost z awokado na zakwasie', 250], ['Podwójne espresso', 60]]],
            ['lunch', 'Obiad', [['Miska z łososiem', 420]]],
            ['snack', 'Przekąska', [['Jogurt grecki z borówkami', 150]]],
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
            [$alex, 'Świeży szpinak', 'Warzywa i owoce', '1 opakowanie', false],
            [$admin, 'Jogurt grecki (duży)', 'Nabiał', '1 kubek', false],
            [$alex, 'Chleb żytni', 'Pieczywo', '1 bochenek', false],
            [$admin, 'Awokado (3 szt.)', 'Warzywa i owoce', '1 opakowanie', true],
            [$alex, 'Pierś z kurczaka', 'Białko', '500 g', true],
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
