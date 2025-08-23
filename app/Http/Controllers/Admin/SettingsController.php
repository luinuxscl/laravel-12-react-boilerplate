<?php

namespace App\Http\Controllers\Admin;

use App\Facades\Settings;
use App\Http\Controllers\Controller;
use App\Models\Setting;
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

        Settings::set($data['key'], $data['value'] ?? null);

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
        Setting::query()->where('key', $key)->delete();
        Settings::forget($key);

        return response()->noContent();
    }
}
