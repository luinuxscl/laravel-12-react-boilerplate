<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    /**
     * Endpoint JSON para DataTable de usuarios (admin).
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filtro de búsqueda simple por nombre o email
        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sortBy', 'id');
        $sortDir = strtolower($request->get('sortDir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSort = ['id','name','email','created_at'];
        if (! in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'id';
        }
        $query->orderBy($sortBy, $sortDir);

        // Paginación
        $perPage = (int) $request->get('perPage', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;
        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => $paginator->items(),
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
        return response()->json(['data' => $user]);
    }

    /**
     * Actualizar campos permitidos de un usuario (admin)
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user->update($data);

        return response()->json(['data' => $user->fresh()]);
    }
}
