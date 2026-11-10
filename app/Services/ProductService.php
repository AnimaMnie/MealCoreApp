<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Logika biznesowa produktów - oddzielona od warstwy HTTP (kontrolera).
 */
class ProductService
{
    /**
     * Lista produktów z wyszukiwaniem po nazwie, filtrem kategorii i paginacją.
     */
    public function list(?string $search, ?string $category, int $perPage = 10): LengthAwarePaginator
    {
        return Product::query()
            ->when($search, fn ($q) => $q->where('name', 'ilike', '%'.$search.'%'))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Product
    {
        // refresh() dociąga wartości domyślne z bazy (np. carbs_100g = 0).
        return Product::create($data)->refresh();
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->refresh();
    }

    /**
     * Reguła biznesowa: nie można usunąć produktu, który jest użyty w czyimś posiłku
     * (zniknęłyby kalorie z historii użytkowników).
     */
    public function delete(Product $product): void
    {
        if ($product->mealItems()->exists()) {
            throw new BusinessRuleException('Nie można usunąć produktu, który jest użyty w zapisanych posiłkach.');
        }

        $product->delete();
    }
}
