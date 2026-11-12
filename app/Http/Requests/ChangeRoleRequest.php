<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dostęp sprawdza middleware "admin" na trasie
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in([User::ROLE_USER, User::ROLE_ADMIN])],
        ];
    }
}
