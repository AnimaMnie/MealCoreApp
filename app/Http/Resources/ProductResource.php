<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * DTO produktu - oddziela format odpowiedzi API od modelu bazy danych.
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'per_100g' => [
                'kcal' => $this->kcal_100g,
                'protein' => $this->protein_100g,
                'carbs' => $this->carbs_100g,
                'fat' => $this->fat_100g,
            ],
        ];
    }
}
