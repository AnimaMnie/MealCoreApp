<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShoppingItemRequest;
use App\Http\Requests\UpdateShoppingItemRequest;
use App\Http\Resources\ShoppingItemResource;
use App\Models\Group;
use App\Models\ShoppingItem;
use App\Services\ShoppingListService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ShoppingItemController extends Controller
{
    public function __construct(private readonly ShoppingListService $shopping)
    {
    }

    /**
     * Lista zakupów grupy (tylko członkowie).
     *
     * @queryParam checked boolean Filtr: 1 = kupione, 0 = do kupienia. Example: 0
     * @queryParam per_page int Liczba wyników na stronę (1-50). Example: 20
     */
    public function index(Request $request, Group $group): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'checked' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $checked = isset($validated['checked']) ? (bool) $validated['checked'] : null;

        return ShoppingItemResource::collection(
            $this->shopping->list($group, $checked, $validated['per_page'] ?? 20)
        );
    }

    public function store(StoreShoppingItemRequest $request, Group $group): ShoppingItemResource
    {
        return new ShoppingItemResource($this->shopping->add($group, $request->user(), $request->validated()));
    }

    /**
     * Edycja lub odhaczenie produktu jako kupionego.
     */
    public function update(UpdateShoppingItemRequest $request, ShoppingItem $shoppingItem): ShoppingItemResource
    {
        return new ShoppingItemResource($this->shopping->update($shoppingItem, $request->validated()));
    }

    public function destroy(ShoppingItem $shoppingItem): Response
    {
        $this->shopping->delete($shoppingItem);

        return response()->noContent();
    }
}
