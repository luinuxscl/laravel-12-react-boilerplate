<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class BoilerplateInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'boilerplate:install {--dev : Seed de datos demo (usuarios de prueba)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Instala la configuración inicial del boilerplate (roles, settings). Con --dev crea usuarios de prueba.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('dev')) {
            $this->info('> [DEV] Reiniciando base de datos (migrate:fresh)...');
            $this->call('migrate:fresh', [
                '--force' => true,
            ]);
        }

        $this->info('> Ejecutando seeders base...');

        $this->callSeeder(RolesSeeder::class);
        $this->callSeeder(SettingsSeeder::class);

        if ($this->option('dev')) {
            $this->newLine();
            $this->info('> Creando usuarios de prueba (--dev)...');
            $this->seedDemoUsers();
        }

        $this->newLine();
        $this->info('Instalación completada.');
        return self::SUCCESS;
    }

    protected function callSeeder(string $seederClass): void
    {
        $this->call('db:seed', [
            '--class' => $seederClass,
            '--no-interaction' => true,
        ]);
    }

    protected function seedDemoUsers(): void
    {
        // Asegurar existencia de roles requeridos
        $rootRole = Role::findOrCreate('root', 'web');
        $adminRole = Role::findOrCreate('admin', 'web');

        $users = [
            [
                'name' => 'Demo User',
                'email' => 'user@demo.com',
                'password' => 'password', // cambiar en entorno real
                'roles' => [], // usuario básico sin rol
            ],
            [
                'name' => 'Demo Admin',
                'email' => 'admin@demo.com',
                'password' => 'password',
                'roles' => ['admin'],
            ],
            [
                'name' => 'Demo Root',
                'email' => 'root@demo.com',
                'password' => 'password',
                'roles' => ['root'],
            ],
        ];

        foreach ($users as $data) {
            /** @var \App\Models\User $user */
            $user = User::query()->firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($data['password']),
                    'email_verified_at' => now(),
                ]
            );

            // Si ya existía, garantizamos nombre verificado para entornos de demo
            if (!$user->wasRecentlyCreated) {
                $user->forceFill([
                    'name' => $data['name'],
                    'email_verified_at' => $user->email_verified_at ?: now(),
                ])->save();
            }

            // Sincronizar roles según política (usuario básico sin rol)
            if (!empty($data['roles'])) {
                $user->syncRoles($data['roles']);
            } else {
                $user->syncRoles([]);
            }

            $this->line(sprintf('- %s (%s)%s', $user->name, $user->email, empty($data['roles']) ? '' : ' · roles: '.implode(',', $data['roles'])));
        }

        $this->warn('Usuarios demo creados con contraseña por defecto: "password". Cambiar en producción.');
    }
}
