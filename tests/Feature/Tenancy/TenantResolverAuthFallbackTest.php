<?php

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function tr_makeTenant(string $slug, bool $isDefault = false): Tenant {
    return Tenant::query()->create([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'domain' => null,
        'is_default' => $isDefault,
    ]);
}

function tr_makeAdminForTenant(Tenant $tenant): User {
    $u = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email_verified_at' => now(),
    ]);
    $u->assignRole('admin');
    return $u;
}

it('falls back to authenticated user tenant when no X-Tenant header on root host', function () {
    $this->seed(RolesSeeder::class);

    $tDemo = tr_makeTenant('demo');
    $tOther = tr_makeTenant('demo2');

    // Seed users in different tenants
    User::factory()->count(2)->create(['tenant_id' => $tDemo->id]);
    User::factory()->count(3)->create(['tenant_id' => $tOther->id]);

    $adminDemo = tr_makeAdminForTenant($tDemo);

    // No X-Tenant header; resolver should use authenticated user's tenant (demo)
    $resp = $this->actingAs($adminDemo)
        ->getJson('/admin/users?perPage=50');

    $resp->assertOk();
    $data = $resp->json('data') ?? [];
    expect($data)->toBeArray();

    foreach ($data as $row) {
        expect($row['tenant_id'] ?? null)->toBe($tDemo->id);
    }
});
