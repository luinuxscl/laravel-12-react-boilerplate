<?php

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedAdminWithTenant(): array {
    test()->seed(RolesSeeder::class);
    $tenant = Tenant::query()->create(['name' => 'Demo', 'slug' => 'demo', 'is_default' => true]);
    $admin = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $admin->assignRole('admin');
    return [$tenant, $admin];
}

it('lists audit logs with pagination and default params', function () {
    [$tenant, $admin] = seedAdminWithTenant();

    // Generate some audit logs directly without a factory
    for ($i = 0; $i < 15; $i++) {
        AuditLog::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'entity_type' => 'user',
            'entity_id' => $admin->id,
            'action' => 'update',
            'changes' => ['name' => ['old' => 'A', 'new' => 'B']],
            'ip' => '127.0.0.1',
            'user_agent' => 'Pest/Tests',
        ]);
    }

    $res = test()->actingAs($admin)
        ->withHeaders(['X-Tenant' => $tenant->slug])
        ->get('/admin/audit-logs');

    $res->assertOk();
    $json = $res->json();
    expect($json)->toHaveKeys(['data', 'meta']);
    expect($json['meta'])->toHaveKeys(['current_page', 'per_page', 'total']);
    expect($json['data'])->not()->toBeEmpty();
});

it('applies filters without 422 when empty strings are provided', function () {
    [$tenant, $admin] = seedAdminWithTenant();

    AuditLog::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $admin->id,
        'entity_type' => 'settings',
        'entity_id' => 0,
        'action' => 'update',
        'changes' => ['k' => ['old' => 'x', 'new' => 'y']],
        'ip' => '127.0.0.1',
        'user_agent' => 'Pest/Tests',
    ]);

    $query = [
        'entity_type' => '',
        'entity_id' => '',
        'action' => '',
        'user_id' => '',
        'created_from' => '',
        'created_to' => '',
        'search' => '',
        'perPage' => '10',
        'page' => '1',
    ];

    $res = test()->actingAs($admin)
        ->withHeaders(['X-Tenant' => $tenant->slug])
        ->get('/admin/audit-logs?' . http_build_query($query));

    $res->assertOk();
});

it('filters by action and entity_type', function () {
    [$tenant, $admin] = seedAdminWithTenant();

    AuditLog::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $admin->id,
        'entity_type' => 'user',
        'entity_id' => $admin->id,
        'action' => 'create',
        'changes' => ['x' => ['old' => null, 'new' => 1]],
        'ip' => '127.0.0.1',
        'user_agent' => 'Pest/Tests',
    ]);
    AuditLog::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $admin->id,
        'entity_type' => 'settings',
        'entity_id' => 0,
        'action' => 'update',
        'changes' => ['y' => ['old' => 1, 'new' => 2]],
        'ip' => '127.0.0.1',
        'user_agent' => 'Pest/Tests',
    ]);

    $res = test()->actingAs($admin)
        ->withHeaders(['X-Tenant' => $tenant->slug])
        ->get('/admin/audit-logs?action=update&entity_type=settings');

    $res->assertOk();
    $json = $res->json();
    foreach ($json['data'] as $row) {
        expect($row['action'])->toBe('update');
        expect($row['entity_type'])->toBe('settings');
    }
});
