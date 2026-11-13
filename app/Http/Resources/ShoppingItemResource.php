<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShoppingItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'group_id' => $this->group_id,
            'product_name' => $this->product_name,
            'category' => $this->category,
            'quantity' => $this->quantity,
            'is_checked' => $this->is_checked,
            'bought_at' => $this->bought_at?->toIso8601String(),
            'added_by' => $this->addedBy?->name,
        ];
    }
}
