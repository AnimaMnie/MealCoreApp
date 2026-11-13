<?php

namespace App\Http\Requests;

use App\Models\Meal;
use App\Services\MealService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'meal_date' => ['required', 'date_format:Y-m-d'],
            'meal_type' => ['required', Rule::in(Meal::TYPES)],
            'name' => ['nullable', 'string', 'max:120'],
            'items' => ['required', 'array', 'min:1', 'max:'.MealService::MAX_ITEMS_PER_MEAL],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.grams' => ['required', 'numeric', 'min:1', 'max:5000'],
        ];
    }
}
