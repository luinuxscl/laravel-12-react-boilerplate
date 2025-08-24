<?php

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeAdmin(): User {
    $user = User::factory()->create(['email_verified_at' => now()]);
    Role::findOrCreate('Admin', 'web');
    $user->assignRole('Admin');
    return $user;
}

function makeRoot(): User {
    $user = User::factory()->create(['email_verified_at' => now()]);
    Role::findOrCreate('root', 'web');
    Role::findOrCreate('Admin', 'web');
    $user->assignRole('root');
    $user->assignRole('Admin'); // acceso a rutas /admin
    return $user;
}

it('admin cannot create the root role (case-insensitive)', function () {
    $this->seed(RolesSeeder::class);
    $admin = makeAdmin();

    $this->actingAs($admin)
        ->postJson('/admin/roles', ['name' => 'root'])
        ->assertStatus(422); // ya existe, validación única aplica

    $this->actingAs($admin)
        ->postJson('/admin/roles', ['name' => 'ROOT'])
        ->assertForbidden();
});

it('admin cannot rename a role to root (case-insensitive)', function () {
    $this->seed(RolesSeeder::class);
    $admin = makeAdmin();
    $role = Role::findOrCreate('TeamLead', 'web');

    $this->actingAs($admin)
        ->putJson("/admin/roles/{$role->id}", ['name' => 'root'])
        ->assertStatus(422); // ya existe, validación única aplica

    $this->actingAs($admin)
        ->putJson("/admin/roles/{$role->id}", ['name' => 'RoOt'])
        ->assertForbidden();
});

it('admin cannot delete the root role', function () {
    $this->seed(RolesSeeder::class);
    $admin = makeAdmin();
    $rootRole = Role::findOrCreate('root', 'web');

    $this->actingAs($admin)
        ->deleteJson("/admin/roles/{$rootRole->id}")
        ->assertForbidden();
});

it('root CAN create/rename/delete root role', function () {
    $this->seed(RolesSeeder::class);
    $root = makeRoot();

    // Primero borrar el rol root existente
    $existingRoot = Role::findOrCreate('root', 'web');
    $this->actingAs($root)
        ->deleteJson("/admin/roles/{$existingRoot->id}")
        ->assertNoContent();

    // Crear nuevamente el rol root
    $this->actingAs($root)
        ->postJson('/admin/roles', ['name' => 'root'])
        ->assertCreated();

    // Renombrar un rol temporal a root (requiere permiso root, y que root no exista)
    // Borramos root antes para evitar 422 por duplicado
    $currentRoot = Role::findOrCreate('root', 'web');
    $this->actingAs($root)
        ->deleteJson("/admin/roles/{$currentRoot->id}")
        ->assertNoContent();

    $role = Role::findOrCreate('TempRole', 'web');
    $this->actingAs($root)
        ->putJson("/admin/roles/{$role->id}", ['name' => 'root'])
        ->assertOk();
});
