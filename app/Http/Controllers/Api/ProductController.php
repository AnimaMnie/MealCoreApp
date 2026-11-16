<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @group Produkty
 *
 * Baza produktów (wartości na 100 g). Odczyt: każdy zalogowany, zapis: tylko admin.
 *
 * Warstwa HTTP: przyjmuje request, deleguje do serwisu, zwraca Resource (DTO).
 */
class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products)
    {
    }

    /**
     * Lista produktów (paginacja + wyszukiwanie).
     *
     * @queryParam q string Szukaj po nazwie. Example: chicken
     * @queryParam category string Filtr kategorii. Example: Protein
     * @queryParam per_page int Liczba wyników na stronę (1-50). Example: 10
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:40'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return ProductResource::collection($this->products->list(
            $validated['q'] ?? null,
            $validated['category'] ?? null,
            $validated['per_page'] ?? 10,
        ));
    }

    public function store(StoreProductRequest $request): ProductResource
    {
        // JsonResource zwrócony z nowo utworzonym modelem daje automatycznie 201 Created.
        return new ProductResource($this->products->create($request->validated()));
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        return new ProductResource($this->products->update($product, $request->validated()));
    }

    public function destroy(Product $product): Response
    {
        $this->products->delete($product);

        return response()->noContent();
    }
}
