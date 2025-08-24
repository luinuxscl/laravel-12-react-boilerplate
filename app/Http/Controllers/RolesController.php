<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleStoreRequest;
use App\Http\Requests\RoleUpdateRequest;
use App\Http\Resources\RoleResource;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class RolesController extends Controller
{
    /**
     * List roles (id, name)
     */
    public function index(): JsonResponse
    {
        $roles = Role::query()->orderBy('name')->get();

        return response()->json([
            'data' => RoleResource::collection($roles),
        ]);
    }

    /**
     * Create role
     */
    public function store(RoleStoreRequest $request): JsonResponse
    {
        $role = Role::findOrCreate($request->validated('name'));

        return response()->json([
            'data' => RoleResource::make($role),
        ], 201);
    }

    /**
     * Delete role
     */
    public function destroy(Role $role): JsonResponse
    {
        $role->delete();

        return response()->json(null, 204);
    }

    /**
     * Update role name
     */
    public function update(RoleUpdateRequest $request, Role $role): JsonResponse
    {
        $role->name = $request->validated('name');
        $role->save();

        return response()->json([
            'data' => RoleResource::make($role),
        ]);
    }
}
