<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'my_role' => $this->whenPivotLoaded('group_members', fn () => $this->pivot->member_role),
            'members_count' => $this->whenCounted('members'),
            'members' => $this->whenLoaded('members', fn () => $this->members->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'member_role' => $member->pivot->member_role,
            ])),
        ];
    }
}
