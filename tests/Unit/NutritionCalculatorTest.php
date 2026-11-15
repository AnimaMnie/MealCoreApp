<?php

namespace Tests\Unit;

use App\Models\Meal;
use App\Models\MealItem;
use App\Models\Product;
use App\Services\NutritionCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Testy jednostkowe logiki liczenia kalorii - bez bazy danych i HTTP.
 */
class NutritionCalculatorTest extends TestCase
{
    private NutritionCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new NutritionCalculator;
    }

    private function product(float $kcal, float $protein = 0, float $carbs = 0, float $fat = 0): Product
    {
        return new Product([
            'name' => 'Test',
            'kcal_100g' => $kcal,
            'protein_100g' => $protein,
            'carbs_100g' => $carbs,
            'fat_100g' => $fat,
        ]);
    }

    private function meal(array $portions): Meal
    {
        $meal = new Meal;
        $items = collect($portions)->map(function (array $portion) {
            $item = new MealItem(['grams' => $portion[1]]);
            $item->setRelation('product', $portion[0]);

            return $item;
        });
        $meal->setRelation('items', $items);

        return $meal;
    }

    public function test_portion_values_are_scaled_by_grams(): void
    {
        // 200 g piersi z kurczaka (165 kcal / 31 g białka na 100 g)
        $result = $this->calculator->forPortion($this->product(165, 31, 0, 3.6), 200);

        $this->assertSame(['kcal' => 330.0, 'protein' => 62.0, 'carbs' => 0.0, 'fat' => 7.2], $result);
    }

    public function test_meal_total_sums_all_items(): void
    {
        $meal = $this->meal([
            [$this->product(100, 10), 150],  // 150 kcal, 15 g białka
            [$this->product(50, 2), 300],    // 150 kcal, 6 g białka
        ]);

        $totals = $this->calculator->forMeal($meal);

        $this->assertSame(300.0, $totals['kcal']);
        $this->assertSame(21.0, $totals['protein']);
    }

    public function test_empty_meal_has_zero_totals(): void
    {
        $this->assertSame(NutritionCalculator::ZERO, $this->calculator->forMeal($this->meal([])));
    }

    public function test_daily_summary_calculates_remaining_calories(): void
    {
        $meals = collect([
            $this->meal([[$this->product(500), 100]]), // 500 kcal
            $this->meal([[$this->product(250), 200]]), // 500 kcal
        ]);

        $summary = $this->calculator->dailySummary($meals, 2000);

        $this->assertSame(2, $summary['meals_count']);
        $this->assertSame(1000.0, $summary['totals']['kcal']);
        $this->assertSame(1000.0, $summary['remaining_kcal']);
        $this->assertSame(50, $summary['goal_progress_percent']);
        $this->assertFalse($summary['goal_exceeded']);
    }

    public function test_daily_summary_detects_exceeded_goal(): void
    {
        $meals = collect([$this->meal([[$this->product(600), 400]])]); // 2400 kcal

        $summary = $this->calculator->dailySummary($meals, 2000);

        $this->assertTrue($summary['goal_exceeded']);
        $this->assertSame(-400.0, $summary['remaining_kcal']);
    }
}
