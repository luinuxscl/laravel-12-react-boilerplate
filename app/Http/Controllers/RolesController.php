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
        $this->authorize('viewAny', Role::class);
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
        // Autorizar creación general de roles via Policy
        $this->authorize('create', Role::class);
        // Protección especial para crear 'root'
        if (strtolower($name) === 'root') {
            \Illuminate\Support\Facades\Gate::authorize('assign-root');
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
        // Autorización por Policy (maneja root-only)
        $this->authorize('delete', $role);

        $role->delete();

        return response()->json(null, 204);
    }

    /**
     * Update role name
     */
    public function update(RoleUpdateRequest $request, Role $role): JsonResponse
    {
        $newName = $request->validated('name');

        // Autorización por Policy (maneja root-only si el rol objetivo es root)
        $this->authorize('update', $role);
        // Adicional: si se intenta renombrar a 'root', exigir privilegio root
        if (strtolower($newName) === 'root') {
            \Illuminate\Support\Facades\Gate::authorize('assign-root');
        }

        $role->name = $newName;
        $role->save();

        return response()->json([
            'data' => RoleResource::make($role),
        ]);
    }
}
