<?php

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('API retorna datos correctos para DataTable solo para Admin', function () {
    $this->seed(RolesSeeder::class);

    // Crear usuarios
    User::factory()->count(15)->create();

    // Usuario normal (no Admin) -> 403
    $user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($user)
        ->getJson('/admin/users')
        ->assertStatus(403);

    // Usuario admin -> 200 + estructura esperada
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('Admin');

    $resp = $this->actingAs($admin)
        ->getJson('/admin/users?perPage=5&sortBy=id&sortDir=desc');

    $resp->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['total','per_page','current_page','last_page'],
        ]);

    $json = $resp->json();
    expect($json['meta']['per_page'])->toBe(5);
    expect($json['meta']['total'])->toBeGreaterThanOrEqual(16); // 15 + admin user + normal user
});
