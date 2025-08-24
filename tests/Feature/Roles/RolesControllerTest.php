<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeAdminUser(): User {
    $user = User::factory()->create();
    Role::findOrCreate('Admin');
    $user->assignRole('Admin');
    return $user;
}

it('lists roles', function () {
    Role::findOrCreate('Admin');
    Role::findOrCreate('Editor');

    $user = makeAdminUser();

    $this->actingAs($user)
        ->getJson('/admin/roles')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'name']]])
        ->assertJsonFragment(['name' => 'Admin'])
        ->assertJsonFragment(['name' => 'Editor']);
});

it('creates a role', function () {
    $user = makeAdminUser();

    $this->actingAs($user)
        ->postJson('/admin/roles', ['name' => 'Manager'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Manager');

    expect(Role::where('name', 'Manager')->where('guard_name', 'web')->exists())->toBeTrue();
});

it('rejects duplicate role names on create', function () {
    Role::findOrCreate('Viewer');
    $user = makeAdminUser();

    $this->actingAs($user)
        ->postJson('/admin/roles', ['name' => 'Viewer'])
        ->assertStatus(422);
});

it('updates a role name', function () {
    $user = makeAdminUser();
    $role = Role::findOrCreate('OldName');

    $this->actingAs($user)
        ->putJson("/admin/roles/{$role->id}", ['name' => 'NewName'])
        ->assertOk()
        ->assertJsonPath('data.name', 'NewName');

    expect(Role::whereKey($role->id)->value('name'))->toBe('NewName');
});

it('rejects duplicate role names on update', function () {
    $user = makeAdminUser();
    Role::findOrCreate('Existing');
    $role = Role::findOrCreate('Temp');

    $this->actingAs($user)
        ->putJson("/admin/roles/{$role->id}", ['name' => 'Existing'])
        ->assertStatus(422);
});

it('deletes a role', function () {
    $user = makeAdminUser();
    $role = Role::findOrCreate('ToDelete');

    $this->actingAs($user)
        ->deleteJson("/admin/roles/{$role->id}")
        ->assertNoContent();

    expect(Role::whereKey($role->id)->exists())->toBeFalse();
});

it('forbids non-admin users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/admin/roles')
        ->assertForbidden();
});
