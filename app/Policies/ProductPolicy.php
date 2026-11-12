<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Authorization produktów: każdy zalogowany może przeglądać,
 * tylko admin może dodawać, edytować i usuwać.
 * Laravel wiąże tę klasę z modelem Product automatycznie (po nazwie).
 */
class ProductPolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }
}
