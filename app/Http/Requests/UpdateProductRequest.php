<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'category' => ['sometimes', 'string', 'in:'.implode(',', StoreProductRequest::CATEGORIES)],
            'kcal_100g' => ['sometimes', 'numeric', 'min:0', 'max:900'],
            'protein_100g' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'carbs_100g' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'fat_100g' => ['sometimes', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
