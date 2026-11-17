<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'date', 'meals_count', 'kcal', 'protein', 'carbs', 'fat', 'calorie_goal', 'goal_exceeded'])]
class DailySummary extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'kcal' => 'float',
            'protein' => 'float',
            'carbs' => 'float',
            'fat' => 'float',
            'goal_exceeded' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
