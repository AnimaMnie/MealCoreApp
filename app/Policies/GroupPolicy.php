<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

/**
 * Grupę (i jej listę zakupów) widzą tylko członkowie lub admin.
 * Usuwać grupę i zarządzać członkami może tylko właściciel.
 */
class GroupPolicy
{
    public function view(User $user, Group $group): bool
    {
        return $user->isAdmin() || $group->hasMember($user);
    }

    public function delete(User $user, Group $group): bool
    {
        return $group->isOwner($user);
    }

    public function manageMembers(User $user, Group $group): bool
    {
        return $group->isOwner($user);
    }
}
