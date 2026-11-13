<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Grupy (rodzina, współlokatorzy, biuro) i ich członkowie.
 */
class GroupService
{
    public const MAX_MEMBERS = 10;

    /**
     * @return Collection<int, Group>
     */
    public function listForUser(User $user): Collection
    {
        return $user->groups()->withCount('members')->orderBy('name')->get();
    }

    /**
     * Reguła biznesowa: nazwa grupy musi być unikalna (bez względu na wielkość liter).
     * Twórca grupy zostaje automatycznie jej właścicielem.
     */
    public function create(User $owner, array $data): Group
    {
        if (Group::whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])->exists()) {
            throw new BusinessRuleException('Grupa o takiej nazwie już istnieje.');
        }

        return DB::transaction(function () use ($owner, $data) {
            $group = Group::create($data);
            $group->members()->attach($owner->id, ['member_role' => Group::ROLE_OWNER]);

            return $group->load('members');
        });
    }

    public function delete(Group $group): void
    {
        $group->delete();
    }

    /**
     * Reguły biznesowe: nie można dodać tej samej osoby dwa razy
     * ani przekroczyć limitu członków grupy.
     */
    public function addMember(Group $group, string $email): Group
    {
        $user = User::where('email', $email)->firstOrFail();

        if ($group->hasMember($user)) {
            throw new BusinessRuleException('Ten użytkownik już należy do grupy.');
        }

        if ($group->members()->count() >= self::MAX_MEMBERS) {
            throw new BusinessRuleException('Grupa może mieć maksymalnie '.self::MAX_MEMBERS.' członków.');
        }

        $group->members()->attach($user->id, ['member_role' => Group::ROLE_MEMBER]);

        return $group->load('members');
    }

    /**
     * Reguła biznesowa: właściciel nie może usunąć sam siebie (grupa zostałaby bez właściciela).
     */
    public function removeMember(Group $group, User $user): Group
    {
        if (! $group->hasMember($user)) {
            throw new BusinessRuleException('Ten użytkownik nie należy do grupy.', 404);
        }

        if ($group->isOwner($user)) {
            throw new BusinessRuleException('Nie można usunąć właściciela z grupy.');
        }

        $group->members()->detach($user->id);

        return $group->load('members');
    }
}
