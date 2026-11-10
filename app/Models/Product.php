<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Wartości odżywcze podane na 100 g produktu.
#[Fillable(['name', 'category', 'kcal_100g', 'protein_100g', 'carbs_100g', 'fat_100g'])]
class Product extends Model
{
    use HasFactory;

    public const CATEGORIES = ['Warzywa i owoce', 'Nabiał', 'Pieczywo', 'Białko', 'Inne'];

    protected function casts(): array
    {
        return [
            'kcal_100g' => 'float',
            'protein_100g' => 'float',
            'carbs_100g' => 'float',
            'fat_100g' => 'float',
        ];
    }

    public function mealItems(): HasMany
    {
        return $this->hasMany(MealItem::class);
    }
}
