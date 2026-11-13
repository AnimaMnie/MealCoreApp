<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddMemberRequest;
use App\Http\Requests\StoreGroupRequest;
use App\Http\Resources\GroupResource;
use App\Models\Group;
use App\Models\User;
use App\Services\GroupService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class GroupController extends Controller
{
    public function __construct(private readonly GroupService $groups)
    {
    }

    /**
     * Grupy, do których należy zalogowany użytkownik.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return GroupResource::collection($this->groups->listForUser($request->user()));
    }

    /**
     * Nowa grupa - twórca zostaje właścicielem.
     */
    public function store(StoreGroupRequest $request): GroupResource
    {
        return new GroupResource($this->groups->create($request->user(), $request->validated()));
    }

    public function show(Group $group): GroupResource
    {
        return new GroupResource($group->load('members'));
    }

    public function destroy(Group $group): Response
    {
        $this->groups->delete($group);

        return response()->noContent();
    }

    /**
     * Dodanie członka po adresie e-mail (tylko właściciel).
     */
    public function addMember(AddMemberRequest $request, Group $group): GroupResource
    {
        return new GroupResource($this->groups->addMember($group, $request->validated('email')));
    }

    /**
     * Usunięcie członka (tylko właściciel).
     */
    public function removeMember(Group $group, User $user): GroupResource
    {
        return new GroupResource($this->groups->removeMember($group, $user));
    }
}
