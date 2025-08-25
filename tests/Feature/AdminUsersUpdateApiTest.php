<?php

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('prohíbe show/update para usuarios sin rol Admin', function () {
    $this->seed(RolesSeeder::class);
    $target = User::factory()->create();
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->getJson("/admin/users/{$target->id}")
        ->assertStatus(403);

    $this->actingAs($user)
        ->putJson("/admin/users/{$target->id}", ['name' => 'New Name'])
        ->assertStatus(403);
});

it('permite a Admin ver y actualizar un usuario', function () {
    $this->seed(RolesSeeder::class);

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $target = User::factory()->create(['name' => 'Old Name']);

    $this->actingAs($admin)
        ->getJson("/admin/users/{$target->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $target->id);

    $this->actingAs($admin)
        ->putJson("/admin/users/{$target->id}", ['name' => 'New Name'])
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name');
});

it('valida nombre requerido al actualizar', function () {
    $this->seed(RolesSeeder::class);

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $target = User::factory()->create();

    $this->actingAs($admin)
        ->putJson("/admin/users/{$target->id}", ['name' => ''])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});
