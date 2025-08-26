<?php

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeTenantS(string $slug, bool $isDefault = false): Tenant {
    return Tenant::query()->create([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'domain' => null,
        'is_default' => $isDefault,
    ]);
}

function makeAdminS(Tenant $tenant): User {
    $u = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email_verified_at' => now(),
    ]);
    $u->assignRole('admin');
    return $u;
}

it('isolates settings per tenant', function () {
    $this->seed(RolesSeeder::class);

    $tA = makeTenantS('demo', true);
    $tB = makeTenantS('demo2');

    $adminA = makeAdminS($tA);
    $adminB = makeAdminS($tB);

    // Tenant A: set setting
    $this->actingAs($adminA)
        ->withHeaders(['X-Tenant' => $tA->slug])
        ->putJson('/admin/settings', [
            'key' => 'site.name',
            'value' => 'Tenant A Site',
        ])->assertOk();

    // Tenant A: read back
    $respA = $this->actingAs($adminA)
        ->withHeaders(['X-Tenant' => $tA->slug])
        ->getJson('/admin/settings');
    $respA->assertOk();
    $dataA = $respA->json('data');
    expect($dataA['site.name'] ?? null)->toBe('Tenant A Site');

    // Tenant B: should not see A's value
    $respB1 = $this->actingAs($adminB)
        ->withHeaders(['X-Tenant' => $tB->slug])
        ->getJson('/admin/settings');
    $respB1->assertOk();
    $dataB1 = $respB1->json('data');
    expect($dataB1['site.name'] ?? null)->not()->toBe('Tenant A Site');

    // Tenant B: set its own value
    $this->actingAs($adminB)
        ->withHeaders(['X-Tenant' => $tB->slug])
        ->putJson('/admin/settings', [
            'key' => 'site.name',
            'value' => 'Tenant B Site',
        ])->assertOk();

    // Tenant B: read back its own value
    $respB2 = $this->actingAs($adminB)
        ->withHeaders(['X-Tenant' => $tB->slug])
        ->getJson('/admin/settings');
    $respB2->assertOk();
    $dataB2 = $respB2->json('data');
    expect($dataB2['site.name'] ?? null)->toBe('Tenant B Site');

    // Tenant A: value remains unchanged
    $respA2 = $this->actingAs($adminA)
        ->withHeaders(['X-Tenant' => $tA->slug])
        ->getJson('/admin/settings');
    $respA2->assertOk();
    $dataA2 = $respA2->json('data');
    expect($dataA2['site.name'] ?? null)->toBe('Tenant A Site');
});
