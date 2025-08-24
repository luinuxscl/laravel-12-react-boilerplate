<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleStoreRequest;
use App\Http\Requests\RoleUpdateRequest;
use App\Http\Resources\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class RolesController extends Controller
{
    /**
     * List roles (id, name)
     */
    public function index(): JsonResponse
    {
        Gate::authorize('manage-roles');
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
        $name = $request->validated('name');
        // Autorizar gestión de roles y protección especial para 'root'
        if (strtolower($name) === 'root') {
            Gate::authorize('assign-root');
        } else {
            Gate::authorize('manage-roles', [null, [$name]]);
        }

        $role = Role::findOrCreate($name, 'web');

        return response()->json([
            'data' => RoleResource::make($role),
        ], 201);
    }

    /**
     * Delete role
     */
    public function destroy(Role $role): JsonResponse
    {
        // Proteger rol 'root': solo root puede eliminarlo
        if (strtolower($role->name) === 'root') {
            Gate::authorize('assign-root');
        } else {
            Gate::authorize('manage-roles', [null, [$role->name]]);
        }

        $role->delete();

        return response()->json(null, 204);
    }

    /**
     * Update role name
     */
    public function update(RoleUpdateRequest $request, Role $role): JsonResponse
    {
        $newName = $request->validated('name');

        // Si el rol objetivo es 'root' o se intenta renombrar a 'root', solo root autorizado
        if (strtolower($role->name) === 'root' || strtolower($newName) === 'root') {
            Gate::authorize('assign-root');
        } else {
            Gate::authorize('manage-roles', [null, [$newName]]);
        }

        $role->name = $newName;
        $role->save();

        return response()->json([
            'data' => RoleResource::make($role),
        ]);
    }
}
