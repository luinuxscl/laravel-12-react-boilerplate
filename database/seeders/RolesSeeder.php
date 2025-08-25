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
            'root', 'admin',
        ];

        foreach ($roles as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }

        // Permisos base del sistema (granularidad por módulo)
        $permissions = [
            // Users
            'users.view',
            'users.manage',
            // Roles
            'roles.view',
            'roles.manage',
            'roles.manage_root', // reservado a root
            // Settings (opcional, ya que existe módulo admin settings)
            'settings.view',
            'settings.manage',
        ];

        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        // Asignación de permisos a roles
        $admin = Role::findByName('admin', 'web');
        $root = Role::findByName('root', 'web');

        // Admin: todos menos roles.manage_root
        $admin->syncPermissions([
            'users.view', 'users.manage',
            'roles.view', 'roles.manage',
            'settings.view', 'settings.manage',
        ]);

        // Root: todos
        $root->syncPermissions($permissions);
    }
}
