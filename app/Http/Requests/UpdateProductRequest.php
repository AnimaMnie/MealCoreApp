<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'category' => ['sometimes', 'string', Rule::in(Product::CATEGORIES)],
            'kcal_100g' => ['sometimes', 'numeric', 'min:0', 'max:900'],
            'protein_100g' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'carbs_100g' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'fat_100g' => ['sometimes', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
