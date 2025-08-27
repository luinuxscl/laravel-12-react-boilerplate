<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogsController extends Controller
{
    /**
     * List paginated audit logs with filters.
     */
    public function index(Request $request): JsonResponse
    {
        // Autorización básica: el grupo de rutas ya exige role:admin|root
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'perPage' => ['sometimes', 'integer', 'in:5,10,25,50,100'],
            'entity_type' => ['sometimes', 'string', 'max:100'],
            'entity_id' => ['sometimes', 'integer'],
            'action' => ['sometimes', 'in:create,update,delete'],
            'user_id' => ['sometimes', 'integer'],
            'created_from' => ['sometimes', 'date'],
            'created_to' => ['sometimes', 'date'],
            'search' => ['sometimes', 'string', 'max:255'], // buscar en ip, user_agent
        ]);

        $perPage = (int)($validated['perPage'] ?? 10);

        $q = AuditLog::query()->latest('id');

        if (!empty($validated['entity_type'])) {
            $q->where('entity_type', $validated['entity_type']);
        }
        if (!empty($validated['entity_id'])) {
            $q->where('entity_id', (int)$validated['entity_id']);
        }
        if (!empty($validated['action'])) {
            $q->where('action', $validated['action']);
        }
        if (!empty($validated['user_id'])) {
            $q->where('user_id', (int)$validated['user_id']);
        }
        if (!empty($validated['created_from'])) {
            $q->whereDate('created_at', '>=', $validated['created_from']);
        }
        if (!empty($validated['created_to'])) {
            $q->whereDate('created_at', '<=', $validated['created_to']);
        }
        if (!empty($validated['search'])) {
            $s = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $validated['search']) . '%';
            $q->where(function ($qq) use ($s) {
                $qq->where('ip', 'like', $s)
                   ->orWhere('user_agent', 'like', $s)
                   ->orWhere('entity_type', 'like', $s)
                   ->orWhere('action', 'like', $s);
            });
        }

        $paginator = $q->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => AuditLogResource::collection($paginator->items()),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
