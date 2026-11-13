<?php

namespace App\Services;

use App\Models\Meal;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Czysta logika liczenia wartości odżywczych (bez bazy i HTTP) - łatwa do testów jednostkowych.
 * Wartości produktów są podane na 100 g, więc mnożymy przez gramaturę / 100.
 */
class NutritionCalculator
{
    public const ZERO = ['kcal' => 0.0, 'protein' => 0.0, 'carbs' => 0.0, 'fat' => 0.0];

    /**
     * @return array{kcal: float, protein: float, carbs: float, fat: float}
     */
    public function forPortion(Product $product, float $grams): array
    {
        $factor = $grams / 100;

        return [
            'kcal' => round($product->kcal_100g * $factor, 1),
            'protein' => round($product->protein_100g * $factor, 1),
            'carbs' => round($product->carbs_100g * $factor, 1),
            'fat' => round($product->fat_100g * $factor, 1),
        ];
    }

    /**
     * Suma dla jednego posiłku (wymaga załadowanych items.product).
     */
    public function forMeal(Meal $meal): array
    {
        return $this->sum($meal->items->map(
            fn ($item) => $this->forPortion($item->product, $item->grams)
        ));
    }

    /**
     * Podsumowanie dnia względem celu kalorycznego użytkownika.
     *
     * @param  Collection<int, Meal>  $meals
     */
    public function dailySummary(Collection $meals, int $calorieGoal): array
    {
        $totals = $this->sum($meals->map(fn (Meal $meal) => $this->forMeal($meal)));

        return [
            'meals_count' => $meals->count(),
            'totals' => $totals,
            'calorie_goal' => $calorieGoal,
            'remaining_kcal' => round($calorieGoal - $totals['kcal'], 1),
            'goal_exceeded' => $totals['kcal'] > $calorieGoal,
            'goal_progress_percent' => $calorieGoal > 0 ? (int) round($totals['kcal'] / $calorieGoal * 100) : 0,
        ];
    }

    /**
     * @param  Collection<int, array>  $parts
     */
    public function sum(Collection $parts): array
    {
        $result = self::ZERO;

        foreach ($parts as $part) {
            foreach ($result as $key => $value) {
                $result[$key] = $value + $part[$key];
            }
        }

        return array_map(fn ($v) => round($v, 1), $result);
    }
}
