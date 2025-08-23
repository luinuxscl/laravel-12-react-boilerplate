<?php

namespace Database\Seeders;

use App\Facades\Settings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Seed de configuraciones iniciales críticas por entorno.
     */
    public function run(): void
    {
        // App/site base
        Settings::set('site.name', config('app.name'));
        Settings::set('site.appearance', [
            'theme' => 'system', // system | light | dark
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
    }
}
