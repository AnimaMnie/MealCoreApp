<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShoppingItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:120'],
            'category' => ['sometimes', Rule::in(Product::CATEGORIES)],
            'quantity' => ['sometimes', 'string', 'max:40'],
        ];
    }
}
