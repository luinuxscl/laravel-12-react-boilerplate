<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Tenant;
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
        $this->info('> Reiniciando base de datos (migrate:fresh)...');
        $this->call('migrate:fresh', [
            '--force' => true,
        ]);

        $this->info('> Ejecutando seeders base...');

        $this->callSeeder(RolesSeeder::class);
        $this->callSeeder(SettingsSeeder::class);

        if ($this->option('dev')) {
            $this->newLine();
            $this->info('> Creando tenants y usuarios de prueba (--dev)...');
            $this->seedDevTenantsAndUsers();
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

    protected function seedDevTenantsAndUsers(): void
    {
        // Roles requeridos
        Role::findOrCreate('root', 'web');
        Role::findOrCreate('admin', 'web');

        // Crear tenants demo
        $demo = Tenant::query()->firstOrCreate(
            ['slug' => 'demo'],
            ['name' => 'Demo', 'domain' => null, 'is_default' => false]
        );
        $demo2 = Tenant::query()->firstOrCreate(
            ['slug' => 'demo2'],
            ['name' => 'Demo 2', 'domain' => null, 'is_default' => false]
        );

        // Usuario root (global, sin tenant)
        $root = $this->firstOrUpdateUser([
            'name' => 'Root',
            'email' => 'root@example.com',
            'password' => 'password',
            'roles' => ['root'],
            'tenant_id' => null,
        ]);

        // Usuarios por tenant demo
        $adminDemo = $this->firstOrUpdateUser([
            'name' => 'Admin Demo',
            'email' => 'admin.demo@example.com',
            'password' => 'password',
            'roles' => ['admin'],
            'tenant_id' => $demo->id,
        ]);
        $userDemo = $this->firstOrUpdateUser([
            'name' => 'User Demo',
            'email' => 'user.demo@example.com',
            'password' => 'password',
            'roles' => [],
            'tenant_id' => $demo->id,
        ]);

        // Usuarios por tenant demo2
        $adminDemo2 = $this->firstOrUpdateUser([
            'name' => 'Admin Demo2',
            'email' => 'admin.demo2@example.com',
            'password' => 'password',
            'roles' => ['admin'],
            'tenant_id' => $demo2->id,
        ]);
        $userDemo2 = $this->firstOrUpdateUser([
            'name' => 'User Demo2',
            'email' => 'user.demo2@example.com',
            'password' => 'password',
            'roles' => [],
            'tenant_id' => $demo2->id,
        ]);

        $this->newLine();
        $this->info('Datos de prueba creados:');
        $this->table(
            ['Tenant', 'Role', 'Name', 'Email', 'Password'],
            [
                ['(global)', 'root', $root->name, $root->email, 'password'],
                ['demo', 'admin', $adminDemo->name, $adminDemo->email, 'password'],
                ['demo', '(user)', $userDemo->name, $userDemo->email, 'password'],
                ['demo2', 'admin', $adminDemo2->name, $adminDemo2->email, 'password'],
                ['demo2', '(user)', $userDemo2->name, $userDemo2->email, 'password'],
            ]
        );

        $this->newLine();
        $this->warn('Contraseñas de desarrollo: "password". No usar en producción.');

        $this->newLine();
        $this->info('Cómo probar en local (http://localhost:8000):');
        $this->line('- Navegador (usa una extensión para enviar headers):');
        $this->line('  * Header: X-Tenant: demo   -> admin.demo@example.com / password');
        $this->line('  * Header: X-Tenant: demo2  -> admin.demo2@example.com / password');
        $this->line('- cURL (login y listado de usuarios):');
        $this->line('  curl -i -c cookies.txt -X POST -H "X-Tenant: demo" -d "email=admin.demo@example.com" -d "password=password" http://localhost:8000/login');
        $this->line('  curl -b cookies.txt -H "X-Tenant: demo" http://localhost:8000/admin/users');
    }

    /**
     * Crear o actualizar usuario idempotente con tenant_id y roles.
     */
    protected function firstOrUpdateUser(array $data): User
    {
        $attributes = ['email' => $data['email']];
        $values = [
            'name' => $data['name'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
            'tenant_id' => $data['tenant_id'] ?? null,
        ];
        /** @var User $user */
        $user = User::query()->firstOrCreate($attributes, $values);
        if (! $user->wasRecentlyCreated) {
            $user->forceFill([
                'name' => $data['name'],
                'tenant_id' => $data['tenant_id'] ?? null,
                'email_verified_at' => $user->email_verified_at ?: now(),
                // Forzar password conocida en entorno de pruebas
                'password' => Hash::make($data['password']),
            ])->save();
        }

        // Sincronizar roles (vacío = sin rol)
        $user->syncRoles($data['roles'] ?? []);

        return $user;
    }
}
