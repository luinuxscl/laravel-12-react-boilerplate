<?php

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedTenantUsersRoles(): array {
    test()->seed(RolesSeeder::class);
    $tenant = Tenant::query()->create(['name' => 'Demo', 'slug' => 'demo', 'is_default' => true]);

    $admin = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $admin->assignRole('admin');

    $basic = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);

    return [$tenant, $admin, $basic];
}

it('allows admin to see roles and forbids basic user', function () {
    [$tenant, $admin, $basic] = seedTenantUsersRoles();

    $res = test()->actingAs($admin)
        ->withHeaders(['X-Tenant' => $tenant->slug])
        ->get('/admin/roles');
    $res->assertOk();

    $res = test()->actingAs($basic)
        ->withHeaders(['X-Tenant' => $tenant->slug])
        ->get('/admin/roles');
    $res->assertForbidden();
});

it('forbids basic user from managing roles', function () {
    [$tenant, $admin, $basic] = seedTenantUsersRoles();

    $res = test()->actingAs($basic)
        ->withHeaders(['X-Tenant' => $tenant->slug, 'X-Requested-With' => 'XMLHttpRequest'])
        ->post('/admin/roles', ['name' => 'Blocked']);
    $res->assertForbidden();
});
