<?php

use App\Models\User;
use App\Models\Tenant;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('API retorna datos correctos para DataTable solo para Admin', function () {
    $this->seed(RolesSeeder::class);

    // Crear tenant y usuarios del tenant
    $tenant = Tenant::query()->create(['name' => 'Demo', 'slug' => 'demo', 'is_default' => true]);
    User::factory()->count(15)->create(['tenant_id' => $tenant->id]);

    // Usuario normal (no Admin) -> 403
    $user = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $this->actingAs($user)->withHeaders(['X-Tenant' => $tenant->slug])
        ->getJson('/admin/users')
        ->assertStatus(403);

    // Usuario admin -> 200 + estructura esperada
    $admin = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $admin->assignRole('admin');

    $resp = $this->actingAs($admin)
        ->withHeaders(['X-Tenant' => $tenant->slug])
        ->getJson('/admin/users?perPage=5&sortBy=id&sortDir=desc');

    $resp->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['total', 'per_page', 'current_page', 'last_page'],
        ]);

    $json = $resp->json();
    expect($json['meta']['per_page'])->toBe(5);
    // 15 del tenant + admin + normal = 17
    expect($json['meta']['total'])->toBeGreaterThanOrEqual(17);
});
