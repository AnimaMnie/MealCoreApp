<?php

namespace App\Policies;

use App\Models\Meal;
use App\Models\User;

/**
 * Posiłek widzi i zmienia tylko jego właściciel.
 */
class MealPolicy
{
    public function view(User $user, Meal $meal): bool
    {
        return (int) $meal->user_id === (int) $user->id;
    }

    public function update(User $user, Meal $meal): bool
    {
        return (int) $meal->user_id === (int) $user->id;
    }

    public function delete(User $user, Meal $meal): bool
    {
        return (int) $meal->user_id === (int) $user->id;
    }
}
