<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public const CATEGORIES = ['Produce', 'Dairy', 'Bakery', 'Protein', 'Other'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'category' => ['sometimes', 'string', 'in:'.implode(',', self::CATEGORIES)],
            'kcal_100g' => ['required', 'numeric', 'min:0', 'max:900'],
            'protein_100g' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'carbs_100g' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'fat_100g' => ['sometimes', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
