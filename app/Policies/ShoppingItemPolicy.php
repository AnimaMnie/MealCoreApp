<?php

namespace App\Policies;

use App\Models\ShoppingItem;
use App\Models\User;

/**
 * Pozycję listy zakupów może zmienić lub usunąć tylko członek tej grupy.
 */
class ShoppingItemPolicy
{
    public function update(User $user, ShoppingItem $item): bool
    {
        return $item->group->hasMember($user);
    }

    public function delete(User $user, ShoppingItem $item): bool
    {
        return $item->group->hasMember($user);
    }
}
