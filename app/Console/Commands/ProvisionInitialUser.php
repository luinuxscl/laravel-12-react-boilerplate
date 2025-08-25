<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ProvisionInitialUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Examples:
     *  php artisan user:provision --email=admin@example.com --name="Initial Admin" --password=secret --role=admin --verified
     *  php artisan user:provision --email=root@example.com --name="Root" --password=secret --role=root --verified
     */
    protected $signature = 'user:provision
        {--email= : Email del usuario}
        {--name= : Nombre del usuario}
        {--password= : Password en texto plano (se hashea)}
        {--role=admin : Rol a asignar (admin|root)}
        {--verified : Marca el email como verificado}
        {--yes : No pedir confirmación interactiva (útil para CI)}
    ';

    /**
     * The console command description.
     */
    protected $description = 'Crea o actualiza de forma idempotente un usuario inicial con rol admin/root para provisión en entornos.';

    public function handle(): int
    {
        $email = (string) $this->option('email');
        $name = (string) $this->option('name');
        $password = (string) $this->option('password');
        $role = strtolower((string) $this->option('role') ?: 'admin');
        $verified = (bool) $this->option('verified');
        $yes = (bool) $this->option('yes');

        if (! in_array($role, ['admin', 'root'], true)) {
            $this->error('El rol debe ser "admin" o "root".');
            return self::INVALID;
        }

        if ($email === '' || $name === '' || $password === '') {
            $this->error('Debe especificar --email, --name y --password.');
            return self::INVALID;
        }

        // Confirmaciones de seguridad
        if (app()->isProduction() && ! $yes) {
            $this->warn('Estás ejecutando provisión en PRODUCCIÓN.');
            if (! $this->confirm('¿Deseas continuar?', false)) {
                return self::INVALID;
            }
        }

        if ($role === 'root' && ! $yes) {
            $this->warn('Vas a crear/actualizar un usuario con rol ROOT (acceso total).');
            if (! $this->confirm('¿Confirmas crear/actualizar ROOT?', false)) {
                return self::INVALID;
            }
        }

        // Asegurar que los roles/permisos base existen
        // Evita fallos si el comando se ejecuta antes de seeders manuales
        $this->callSilent('db:seed', ['--class' => RolesSeeder::class, '--no-interaction' => true]);

        // También se asegura la existencia por si el seeder cambia el guard
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('root', 'web');

        /** @var User $user */
        $user = User::query()->firstOrNew(['email' => $email]);
        $isNew = ! $user->exists;

        $user->name = $name;
        $user->password = Hash::make($password);
        if ($verified) {
            $user->email_verified_at = $user->email_verified_at ?: now();
        }
        $user->save();

        $user->syncRoles([$role]);

        $this->info(sprintf('%s usuario %s (%s) con rol %s.', $isNew ? 'Creado' : 'Actualizado', $user->name, $user->email, $role));
        if ($isNew) {
            $this->warn('Guarda las credenciales de forma segura. La contraseña no vuelve a mostrarse.');
        }

        return self::SUCCESS;
    }
}
