<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    use ApiResponses;

    public function index(): JsonResponse
    {
        $roles = Role::all();

        return $this->ok('Roles retrieved', [
            'roles' => RoleResource::collection($roles),
        ]);
    }

    public function show(Role $role): JsonResponse
    {
        return $this->ok('Role retrieved', [
            'role' => new RoleResource($role),
        ]);
    }
}
