<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear roles base del sistema
        // Política:
        // - Usuarios básicos NO llevan rol asignado por defecto.
        // - 'root': rol reservado a desarrolladores del sistema (super admin). Solo otro 'root' puede hacer CRUD sobre usuarios 'root'.
        // - 'admin': rol administrativo; puede configurar la app y gestionar usuarios y otros 'admin'.
        $roles = [
            'root',
            'admin',
        ];

        foreach ($roles as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }

        // Nota: la asignación de permisos específicos se definirá según necesidad.
        // Ejemplo (descomentando cuando se definan permisos):
        // Permission::findOrCreate('users.manage', 'web');
        // Role::findByName('admin', 'web')->givePermissionTo('users.manage');
    }
}
