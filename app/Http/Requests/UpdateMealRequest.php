<?php

namespace App\Http\Requests;

use App\Models\Meal;
use App\Services\MealService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'meal_date' => ['sometimes', 'date_format:Y-m-d'],
            'meal_type' => ['sometimes', Rule::in(Meal::TYPES)],
            'name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'items' => ['sometimes', 'array', 'min:1', 'max:'.MealService::MAX_ITEMS_PER_MEAL],
            'items.*.product_id' => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.grams' => ['required_with:items', 'numeric', 'min:1', 'max:5000'],
        ];
    }
}
