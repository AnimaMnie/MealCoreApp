<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'type'])]
class Group extends Model
{
    use HasFactory;

    public const ROLE_OWNER = 'owner';
    public const ROLE_MEMBER = 'member';

    public const TYPES = ['family', 'roommates', 'office', 'other'];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_members')
            ->withPivot('member_role')
            ->withTimestamps();
    }

    public function shoppingItems(): HasMany
    {
        return $this->hasMany(ShoppingItem::class);
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->whereKey($user->id)->exists();
    }

    public function isOwner(User $user): bool
    {
        return $this->members()
            ->whereKey($user->id)
            ->wherePivot('member_role', self::ROLE_OWNER)
            ->exists();
    }
}
