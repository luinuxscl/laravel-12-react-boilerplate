<?php

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('UsersController show devuelve UserResource shape', function () {
    $this->seed(RolesSeeder::class);

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $target = User::factory()->create(['name' => 'Target User']);

    $resp = $this->actingAs($admin)->getJson("/admin/users/{$target->id}");
    $resp->assertOk()
        ->assertJsonStructure([
            'data' => ['id', 'name', 'email', 'email_verified_at', 'created_at', 'updated_at'],
        ])
        ->assertJsonPath('data.id', $target->id)
        ->assertJsonPath('data.name', 'Target User');
});

it('UsersController update devuelve UserResource shape', function () {
    $this->seed(RolesSeeder::class);

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $target = User::factory()->create(['name' => 'Old']);

    $resp = $this->actingAs($admin)->putJson("/admin/users/{$target->id}", ['name' => 'New']);
    $resp->assertOk()
        ->assertJsonStructure([
            'data' => ['id', 'name', 'email', 'email_verified_at', 'created_at', 'updated_at'],
        ])
        ->assertJsonPath('data.name', 'New');
});
