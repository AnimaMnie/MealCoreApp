<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Group;
use App\Models\ShoppingItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Wspólna lista zakupów grupy. Dostęp mają tylko członkowie grupy (GroupPolicy / ShoppingItemPolicy).
 */
class ShoppingListService
{
    public function list(Group $group, ?bool $checked, int $perPage = 20): LengthAwarePaginator
    {
        return $group->shoppingItems()
            ->with('addedBy')
            ->when($checked !== null, fn ($q) => $q->where('is_checked', $checked))
            ->orderBy('is_checked')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Reguła biznesowa: na liście nie może być dwa razy tego samego niekupionego produktu.
     */
    public function add(Group $group, User $user, array $data): ShoppingItem
    {
        $duplicate = $group->shoppingItems()
            ->where('is_checked', false)
            ->whereRaw('LOWER(product_name) = ?', [mb_strtolower($data['product_name'])])
            ->exists();

        if ($duplicate) {
            throw new BusinessRuleException('Ten produkt jest już na liście zakupów.');
        }

        $item = $group->shoppingItems()->create($data + ['added_by' => $user->id]);

        return $item->refresh()->load('addedBy');
    }

    /**
     * Odhaczenie produktu zapisuje też czas zakupu (bought_at), odznaczenie go czyści.
     */
    public function update(ShoppingItem $item, array $data): ShoppingItem
    {
        if (array_key_exists('is_checked', $data)) {
            $data['bought_at'] = $data['is_checked'] ? now() : null;
        }

        $item->update($data);

        return $item->refresh()->load('addedBy');
    }

    public function delete(ShoppingItem $item): void
    {
        $item->delete();
    }
}
