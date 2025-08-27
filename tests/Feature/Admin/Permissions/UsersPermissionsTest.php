<?php

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedTenantUsers(): array {
    test()->seed(RolesSeeder::class);
    $tenant = Tenant::query()->create(['name' => 'Demo', 'slug' => 'demo', 'is_default' => true]);

    $admin = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $admin->assignRole('admin');

    $basic = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);

    return [$tenant, $admin, $basic];
}

it('allows admin to list users and forbids basic user', function () {
    [$tenant, $admin, $basic] = seedTenantUsers();

    // admin can access
    $res = test()->actingAs($admin)
        ->withHeaders(['X-Tenant' => $tenant->slug])
        ->get('/admin/users');
    $res->assertOk();

    // basic user forbidden
    $res = test()->actingAs($basic)
        ->withHeaders(['X-Tenant' => $tenant->slug])
        ->get('/admin/users');
    $res->assertForbidden();
});

it('forbids basic user from viewing a user detail', function () {
    [$tenant, $admin, $basic] = seedTenantUsers();

    $target = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);

    $res = test()->actingAs($basic)
        ->withHeaders(['X-Tenant' => $tenant->slug])
        ->get('/admin/users/' . $target->id);
    $res->assertForbidden();
});

it('forbids basic user from updating or deleting users', function () {
    [$tenant, $admin, $basic] = seedTenantUsers();

    $target = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);

    // Update (should be blocked by permission: users.manage)
    $res = test()->actingAs($basic)
        ->withHeaders(['X-Tenant' => $tenant->slug, 'X-Requested-With' => 'XMLHttpRequest'])
        ->put('/admin/users/' . $target->id, ['name' => 'Blocked']);
    $res->assertForbidden();

    // Delete (should be blocked by permission: users.manage)
    $res = test()->actingAs($basic)
        ->withHeaders(['X-Tenant' => $tenant->slug, 'X-Requested-With' => 'XMLHttpRequest'])
        ->delete('/admin/users/' . $target->id);
    $res->assertForbidden();
});
