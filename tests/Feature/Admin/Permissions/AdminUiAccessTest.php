<?php

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedTenantUsersUi(): array {
    test()->seed(RolesSeeder::class);
    $tenant = Tenant::query()->create(['name' => 'Demo', 'slug' => 'demo', 'is_default' => true]);

    $admin = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $admin->assignRole('admin');

    $basic = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);

    return [$tenant, $admin, $basic];
}

it('allows admin to access admin UI pages and forbids basic user', function () {
    [$tenant, $admin, $basic] = seedTenantUsersUi();

    $uiPages = [
        '/admin/users-ui',
        '/admin/roles-ui',
        '/admin/settings-ui',
    ];

    foreach ($uiPages as $path) {
        test()->actingAs($admin)
            ->withHeaders(['X-Tenant' => $tenant->slug])
            ->get($path)
            ->assertOk();

        test()->actingAs($basic)
            ->withHeaders(['X-Tenant' => $tenant->slug])
            ->get($path)
            ->assertForbidden();
    }
});
