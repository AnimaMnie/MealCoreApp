<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Zarządzanie użytkownikami przez admina.
 */
class UserManagementService
{
    public function list(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'ilike', '%'.$search.'%')
                    ->orWhere('email', 'ilike', '%'.$search.'%');
            }))
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Reguła biznesowa: admin nie może odebrać uprawnień samemu sobie
     * (mógłby zablokować dostęp do panelu admina).
     */
    public function changeRole(User $actor, User $target, string $role): User
    {
        if ($actor->is($target) && $role !== User::ROLE_ADMIN) {
            throw new BusinessRuleException('Nie możesz odebrać uprawnień administratora samemu sobie.');
        }

        $target->role = $role;
        $target->save();

        return $target;
    }

    /**
     * Reguła biznesowa: admin nie może usunąć własnego konta.
     */
    public function delete(User $actor, User $target): void
    {
        if ($actor->is($target)) {
            throw new BusinessRuleException('Nie możesz usunąć własnego konta.');
        }

        $target->delete();
    }
}
