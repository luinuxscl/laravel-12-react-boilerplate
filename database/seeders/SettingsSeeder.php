<?php

namespace Database\Seeders;

use App\Facades\Settings;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Seed de configuraciones iniciales críticas por entorno.
     */
    public function run(): void
    {
        // Resolver tenant default y setear contexto temporal
        $tenant = Tenant::query()->where('is_default', true)->first();
        $context = app(TenantContext::class);
        $original = $context->get();
        if ($tenant) {
            $context->set($tenant);
        }

        // App/site base
        Settings::set('site.name', config('app.name'));
        Settings::set('site.appearance', [
            'theme' => 'system', // system | light | dark
        ]);

        // Branding (por defecto sin assets)
        Settings::set('site.brand', [
            'logo_url' => null,
            'favicon_url' => null,
        ]);

        // Notificaciones
        Settings::set('notifications.enabled', true);

        // Email remitente
        Settings::set('mail.from', [
            'address' => config('mail.from.address', 'no-reply@example.com'),
            'name' => config('mail.from.name', config('app.name')),
        ]);

        // Seguridad
        Settings::set('security.password_min_length', 8);

        // Restaurar contexto
        $context->set($original);
    }
}
