<?php

use App\Models\User;
use App\Models\Tenant;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

it('users index: forbids non-admin and allows admin', function () {
    $tenant = Tenant::query()->create(['name' => 'Demo', 'slug' => 'demo', 'is_default' => true]);
    $normal = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);

    $this->actingAs($normal)->withHeaders(['X-Tenant' => $tenant->slug])
        ->getJson('/admin/users')
        ->assertStatus(403);

    $admin = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $admin->assignRole('admin');

    $this->actingAs($admin)->withHeaders(['X-Tenant' => $tenant->slug])
        ->getJson('/admin/users')
        ->assertOk();
});

it('users show: admin can view, non-admin 403', function () {
    $tenant = Tenant::query()->create(['name' => 'Demo', 'slug' => 'demo', 'is_default' => true]);
    $target = User::factory()->create(['tenant_id' => $tenant->id]);

    $normal = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $this->actingAs($normal)->withHeaders(['X-Tenant' => $tenant->slug])
        ->getJson("/admin/users/{$target->id}")
        ->assertStatus(403);

    $admin = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $admin->assignRole('admin');

    $this->actingAs($admin)->withHeaders(['X-Tenant' => $tenant->slug])
        ->getJson("/admin/users/{$target->id}")
        ->assertOk();
});

it('users update: admin can update non-root users; non-admin 403', function () {
    $tenant = Tenant::query()->create(['name' => 'Demo', 'slug' => 'demo', 'is_default' => true]);
    $target = User::factory()->create(['name' => 'Old Name', 'tenant_id' => $tenant->id]);

    $normal = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $this->actingAs($normal)->withHeaders(['X-Tenant' => $tenant->slug])
        ->putJson("/admin/users/{$target->id}", ['name' => 'New Name'])
        ->assertStatus(403);

    $admin = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $admin->assignRole('admin');

    $this->actingAs($admin)->withHeaders(['X-Tenant' => $tenant->slug])
        ->putJson("/admin/users/{$target->id}", ['name' => 'New Name'])
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name');
});

it('users destroy: admin can delete non-root users; non-admin 403; cannot delete self (422)', function () {
    // Target user (non-root)
    $tenant = Tenant::query()->create(['name' => 'Demo', 'slug' => 'demo', 'is_default' => true]);
    $victim = User::factory()->create(['tenant_id' => $tenant->id]);

    // Non-admin
    $normal = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $this->actingAs($normal)->withHeaders(['X-Tenant' => $tenant->slug])
        ->deleteJson("/admin/users/{$victim->id}")
        ->assertStatus(403);

    // Admin can delete victim
    $admin = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $admin->assignRole('admin');
    $this->actingAs($admin)->withHeaders(['X-Tenant' => $tenant->slug])
        ->deleteJson("/admin/users/{$victim->id}")
        ->assertNoContent(); // 204

    // Admin cannot delete self
    $this->actingAs($admin)->withHeaders(['X-Tenant' => $tenant->slug])
        ->deleteJson("/admin/users/{$admin->id}")
        ->assertStatus(422);
});

it('users policies: admin cannot manage root users (403)', function () {
    $tenant = Tenant::query()->create(['name' => 'Demo', 'slug' => 'demo', 'is_default' => true]);
    $rootUser = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $rootUser->assignRole('root');

    $admin = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $admin->assignRole('admin');

    // Show is allowed to viewAny, but individual view is restricted by policy if root-only? Keep to update/delete checks.
    $this->actingAs($admin)->withHeaders(['X-Tenant' => $tenant->slug])
        ->putJson("/admin/users/{$rootUser->id}", ['name' => 'X'])
        ->assertStatus(403);

    $this->actingAs($admin)->withHeaders(['X-Tenant' => $tenant->slug])
        ->deleteJson("/admin/users/{$rootUser->id}")
        ->assertStatus(403);
});
