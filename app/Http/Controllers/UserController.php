<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Filters\UserFilter;
use App\Http\Requests\ListUsersRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    use ApiResponses;

    public function index(ListUsersRequest $request, UserFilter $filters): JsonResponse
    {
        $response = Gate::inspect('viewAny', User::class);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $query = User::query()
            ->with(['role', 'company'])
            ->filter($filters);

        $filters->applyUserSorting($query);

        $users = $query->paginate($filters->perPage());

        return $this->ok('Users retrieved', [
            'users' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $response = Gate::inspect('create', User::class);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $data = $request->validated();

        if ($this->isAssigningSuperAdminRole($data['role_id'])) {
            $assignSuperAdminResponse = Gate::inspect('assignSuperAdmin', User::class);

            if ($assignSuperAdminResponse->denied()) {
                return $this->error($assignSuperAdminResponse->message(), 403);
            }
        }

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role_id' => $data['role_id'],
            'company_id' => $data['company_id'] ?? null,
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'] ?? UserStatus::Active,
            'created_by' => $request->user()->id,
        ]);

        $user->load(['role', 'company']);

        return $this->success('User created successfully', [
            'user' => new UserResource($user),
        ], 201);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $response = Gate::inspect('view', $user);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $user->load(['role', 'company']);

        return $this->ok('User retrieved', [
            'user' => new UserResource($user),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $response = Gate::inspect('update', $user);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $data = $request->validated();

        if (isset($data['role_id']) && $data['role_id'] !== $user->role_id) {
            $manageRolesResponse = Gate::inspect('manageRoles', $user);

            if ($manageRolesResponse->denied()) {
                return $this->error($manageRolesResponse->message(), 403);
            }

            if ($this->isAssigningSuperAdminRole($data['role_id'])) {
                $assignSuperAdminResponse = Gate::inspect('assignSuperAdmin', User::class);

                if ($assignSuperAdminResponse->denied()) {
                    return $this->error($assignSuperAdminResponse->message(), 403);
                }
            }
        }

        if (isset($data['password'])) {
            $data['password_changed_at'] = now();
        }

        $user->update($data);
        $user->load(['role', 'company']);

        return $this->ok('User updated successfully', [
            'user' => new UserResource($user),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $response = Gate::inspect('delete', $user);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $user->delete();

        return $this->ok('User deleted successfully');
    }

    protected function isAssigningSuperAdminRole(int $roleId): bool
    {
        $role = Role::find($roleId);

        return $role && $role->slug === 'super_admin';
    }
}
