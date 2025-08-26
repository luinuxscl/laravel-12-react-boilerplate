<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    /**
     * Endpoint JSON para DataTable de usuarios (admin).
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);
        // Validación de filtros y parámetros de paginación/orden
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'created_from' => ['nullable', 'date_format:Y-m-d'],
            'created_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:created_from'],
            'sortBy' => ['nullable', 'in:id,name,email,created_at'],
            'sortDir' => ['nullable', 'in:asc,desc'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = User::query()->with('roles');

        // Scope por tenant (read-only listing)
        if (config('tenancy.enabled', true)) {
            $tenantId = app(TenantContext::class)->id();
            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }
        }

        // Filtro de búsqueda simple por nombre o email
        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filtro por rol (Spatie)
        if ($role = $request->string('role')->toString()) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        // Filtros por fecha de creación (YYYY-MM-DD)
        if ($from = $request->string('created_from')->toString()) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->string('created_to')->toString()) {
            $query->whereDate('created_at', '<=', $to);
        }

        // Ordenamiento
        $sortBy = $request->get('sortBy', 'id');
        $sortDir = strtolower($request->get('sortDir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSort = ['id', 'name', 'email', 'created_at'];
        if (! in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'id';
        }
        $query->orderBy($sortBy, $sortDir);

        // Paginación
        $perPage = (int) $request->get('perPage', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;
        $paginator = $query->paginate($perPage);

        // Wrap items with Resource and keep meta intact
        $items = UserResource::collection(collect($paginator->items()));

        return response()->json([
            'data' => $items,
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Mostrar detalle de usuario (admin)
     */
    public function show(User $user)
    {
        // Bloquear acceso cross-tenant en PoC
        if (config('tenancy.enabled', true)) {
            $tenantId = app(TenantContext::class)->id();
            if ($tenantId && $user->tenant_id !== $tenantId) {
                abort(404);
            }
        }
        $this->authorize('view', $user);
        $user->load('roles');

        return response()->json(['data' => UserResource::make($user)]);
    }

    /**
     * Actualizar campos permitidos de un usuario (admin)
     */
    public function update(Request $request, User $user)
    {
        // Bloquear acceso cross-tenant en PoC
        if (config('tenancy.enabled', true)) {
            $tenantId = app(TenantContext::class)->id();
            if ($tenantId && $user->tenant_id !== $tenantId) {
                abort(404);
            }
        }
        $this->authorize('update', $user);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user->update($data);

        return response()->json(['data' => UserResource::make($user->fresh())]);
    }

    /**
     * Eliminar usuario (admin)
     */
    public function destroy(Request $request, User $user)
    {
        // Bloquear acceso cross-tenant en PoC
        if (config('tenancy.enabled', true)) {
            $tenantId = app(TenantContext::class)->id();
            if ($tenantId && $user->tenant_id !== $tenantId) {
                abort(404);
            }
        }
        $this->authorize('delete', $user);
        // Evitar auto-eliminación accidental via endpoint admin
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'You cannot delete yourself.'], 422);
        }

        $user->delete();

        return response()->noContent(); // 204
    }
}
