<?php

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedTenantUsersSettings(): array {
    test()->seed(RolesSeeder::class);
    $tenant = Tenant::query()->create(['name' => 'Demo', 'slug' => 'demo', 'is_default' => true]);

    $admin = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $admin->assignRole('admin');

    $basic = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);

    return [$tenant, $admin, $basic];
}

it('allows admin to view settings and forbids basic user', function () {
    [$tenant, $admin, $basic] = seedTenantUsersSettings();

    $res = test()->actingAs($admin)
        ->withHeaders(['X-Tenant' => $tenant->slug])
        ->get('/admin/settings');
    $res->assertOk();

    $res = test()->actingAs($basic)
        ->withHeaders(['X-Tenant' => $tenant->slug])
        ->get('/admin/settings');
    $res->assertForbidden();
});

it('forbids basic user from updating settings', function () {
    [$tenant, $admin, $basic] = seedTenantUsersSettings();

    $payload = ['key' => 'site.name', 'value' => 'Blocked'];
    $res = test()->actingAs($basic)
        ->withHeaders(['X-Tenant' => $tenant->slug, 'X-Requested-With' => 'XMLHttpRequest'])
        ->put('/admin/settings', $payload);
    $res->assertForbidden();
});
