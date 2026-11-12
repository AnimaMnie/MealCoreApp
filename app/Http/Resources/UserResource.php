<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * DTO użytkownika - nigdy nie zwraca hasła ani tokenów.
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'daily_calorie_goal' => $this->daily_calorie_goal,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
