<?php

namespace App\Http\Resources;

use App\Services\NutritionCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MealResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $calculator = app(NutritionCalculator::class);

        return [
            'id' => $this->id,
            'meal_date' => $this->meal_date->toDateString(),
            'meal_type' => $this->meal_type,
            'name' => $this->name,
            'items' => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'grams' => $item->grams,
                'nutrition' => $calculator->forPortion($item->product, $item->grams),
            ]),
            'totals' => $calculator->forMeal($this->resource),
        ];
    }
}
