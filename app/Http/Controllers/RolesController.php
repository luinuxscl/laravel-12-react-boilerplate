<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleStoreRequest;
use App\Http\Requests\RoleUpdateRequest;
use App\Http\Resources\RoleResource;
use App\Services\AuditLogger;
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

        // Audit log
        app(AuditLogger::class)->log('create', $role, null, [
            'attributes' => ['name' => $role->name],
        ]);

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

        $snapshot = $role->only(['id', 'name']);
        $role->delete();

        // Audit log
        app(AuditLogger::class)->log('delete', Role::class, $snapshot['id'], [
            'snapshot' => $snapshot,
        ]);

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

        $before = $role->only(['name']);
        $role->name = $newName;
        $role->save();
        $after = $role->only(['name']);

        // Audit log
        app(AuditLogger::class)->log('update', $role, null, [
            'before' => $before,
            'after' => $after,
        ]);

        return response()->json([
            'data' => RoleResource::make($role),
        ]);
    }
}
