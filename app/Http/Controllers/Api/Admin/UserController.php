<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @group Panel admina
 *
 * Zarządzanie użytkownikami - tylko dla roli admin.
 */
class UserController extends Controller
{
    public function __construct(private readonly UserManagementService $users)
    {
    }

    /**
     * Lista użytkowników (paginacja + wyszukiwanie po nazwie / e-mailu).
     *
     * @queryParam q string Szukaj po nazwie lub e-mailu. Example: alex
     * @queryParam per_page int Liczba wyników na stronę (1-50). Example: 15
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return UserResource::collection(
            $this->users->list($validated['q'] ?? null, $validated['per_page'] ?? 15)
        );
    }

    /**
     * Zmiana roli użytkownika (user / admin).
     */
    public function updateRole(ChangeRoleRequest $request, User $user): UserResource
    {
        return new UserResource(
            $this->users->changeRole($request->user(), $user, $request->validated('role'))
        );
    }

    public function destroy(Request $request, User $user): Response
    {
        $this->users->delete($request->user(), $user);

        return response()->noContent();
    }
}
