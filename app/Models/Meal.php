<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'meal_date', 'meal_type', 'name'])]
class Meal extends Model
{
    use HasFactory;

    public const TYPES = ['breakfast', 'second_breakfast', 'lunch', 'dinner', 'snack', 'supper'];

    protected function casts(): array
    {
        return [
            'meal_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MealItem::class);
    }
}
