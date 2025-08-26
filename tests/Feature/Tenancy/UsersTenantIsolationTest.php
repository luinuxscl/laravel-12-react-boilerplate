<?php

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeTenant(string $slug, bool $isDefault = false): Tenant {
    return Tenant::query()->create([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'domain' => null,
        'is_default' => $isDefault,
    ]);
}

function makeAdminForTenant(Tenant $tenant): User {
    $u = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email_verified_at' => now(),
    ]);
    $u->assignRole('admin');
    return $u;
}

it('lists only users from current tenant', function () {
    $this->seed(RolesSeeder::class);

    $tA = makeTenant('demo', true);
    $tB = makeTenant('demo2');

    // Users for tenant A
    User::factory()->count(3)->create(['tenant_id' => $tA->id]);
    // Users for tenant B
    User::factory()->count(5)->create(['tenant_id' => $tB->id]);

    $adminA = makeAdminForTenant($tA);

    $resp = $this->actingAs($adminA)
        ->withHeaders(['X-Tenant' => $tA->slug])
        ->getJson('/admin/users?perPage=50');

    $resp->assertOk();
    $data = $resp->json('data');
    expect($data)->toBeArray();
    // All returned users must belong to tenant A
    foreach ($data as $row) {
        expect($row['tenant_id'] ?? null)->toBe($tA->id);
    }
});

it('blocks cross-tenant show/update/destroy with 404', function () {
    $this->seed(RolesSeeder::class);

    $tA = makeTenant('demo', true);
    $tB = makeTenant('demo2');

    $adminA = makeAdminForTenant($tA);

    $userB = User::factory()->create([
        'tenant_id' => $tB->id,
        'email_verified_at' => now(),
    ]);

    // show
    $this->actingAs($adminA)
        ->withHeaders(['X-Tenant' => $tA->slug])
        ->getJson("/admin/users/{$userB->id}")
        ->assertStatus(404);

    // update
    $this->actingAs($adminA)
        ->withHeaders(['X-Tenant' => $tA->slug])
        ->putJson("/admin/users/{$userB->id}", ['name' => 'Hacked'])
        ->assertStatus(404);

    // destroy
    $this->actingAs($adminA)
        ->withHeaders(['X-Tenant' => $tA->slug])
        ->deleteJson("/admin/users/{$userB->id}")
        ->assertStatus(404);
});
