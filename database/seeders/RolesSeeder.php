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
        // Crear roles base
        $roles = [
            'Super Admin',
            'Admin',
            'Editor',
            'User',
        ];

        foreach ($roles as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }

        // Ejemplo: permisos críticos mínimos (defínelos según necesidad futura)
        // Permission::findOrCreate('users.manage', 'web');
        // Role::findByName('Admin', 'web')->givePermissionTo('users.manage');
    }
}
