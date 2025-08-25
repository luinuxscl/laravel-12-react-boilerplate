<?php

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('usuario puede tener roles', function () {
    $this->seed(RolesSeeder::class);

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user->assignRole('admin');

    expect($user->hasRole('admin'))->toBeTrue();
});

it('middleware bloquea acceso no autorizado a ruta admin', function () {
    $this->seed(RolesSeeder::class);

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Usuario sin rol admin intenta acceder
    $response = $this->actingAs($user)->get('/admin-only');

    $response->assertStatus(403);
});

it('admin puede gestionar usuarios (policy viewAny)', function () {
    $this->seed(RolesSeeder::class);

    $admin = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $admin->assignRole('admin');

    expect($admin->can('viewAny', User::class))->toBeTrue();
});
