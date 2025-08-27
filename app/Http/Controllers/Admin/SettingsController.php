<?php

namespace App\Http\Controllers\Admin;

use App\Facades\Settings;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Renderiza la página de administración de Settings.
     */
    public function page()
    {
        return Inertia::render('admin/settings/index');
    }

    /**
     * Lista todas las configuraciones (clave => valor).
     */
    public function index()
    {
        return response()->json([
            'data' => Settings::all(),
        ]);
    }

    /**
     * Crea/actualiza una configuración.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:255'],
            'value' => ['nullable'], // valor puede ser escalar o array JSON
        ]);

        $before = [
            'key' => $data['key'],
            'value' => Settings::get($data['key']),
        ];
        Settings::set($data['key'], $data['value'] ?? null);
        $after = [
            'key' => $data['key'],
            'value' => Settings::get($data['key']),
        ];

        // Audit log
        app(AuditLogger::class)->log('update', Setting::class, $data['key'], [
            'before' => $before,
            'after' => $after,
        ]);

        return response()->json([
            'data' => [
                'key' => $data['key'],
                'value' => Settings::get($data['key']),
            ],
        ]);
    }

    /**
     * Elimina una configuración por clave.
     */
    public function destroy(string $key)
    {
        $snapshot = [
            'key' => $key,
            'value' => Settings::get($key),
        ];
        Setting::query()->where('key', $key)->delete();
        Settings::forget($key);

        // Audit log
        app(AuditLogger::class)->log('delete', Setting::class, $key, [
            'snapshot' => $snapshot,
        ]);

        return response()->noContent();
    }
}
