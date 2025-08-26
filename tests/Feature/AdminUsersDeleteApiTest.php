<?php

use App\Models\User;
use App\Models\Tenant;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('prohíbe delete para usuarios sin rol Admin', function () {
    $this->seed(RolesSeeder::class);
    $tenant = Tenant::query()->create(['name' => 'Demo', 'slug' => 'demo', 'is_default' => true]);
    $target = User::factory()->create(['tenant_id' => $tenant->id]);
    $user = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);

    $this->actingAs($user)->withHeaders(['X-Tenant' => $tenant->slug])
        ->deleteJson("/admin/users/{$target->id}")
        ->assertStatus(403);
});

it('impide auto-eliminación por Admin', function () {
    $this->seed(RolesSeeder::class);
    $tenant = Tenant::query()->create(['name' => 'Demo', 'slug' => 'demo', 'is_default' => true]);
    $admin = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $admin->assignRole('admin');

    $this->actingAs($admin)->withHeaders(['X-Tenant' => $tenant->slug])
        ->deleteJson("/admin/users/{$admin->id}")
        ->assertStatus(422)
        ->assertJsonPath('message', 'You cannot delete yourself.');
});

it('permite a Admin eliminar a otro usuario', function () {
    $this->seed(RolesSeeder::class);
    $tenant = Tenant::query()->create(['name' => 'Demo', 'slug' => 'demo', 'is_default' => true]);
    $admin = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $admin->assignRole('admin');

    $target = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($admin)->withHeaders(['X-Tenant' => $tenant->slug])
        ->deleteJson("/admin/users/{$target->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('users', ['id' => $target->id]);
});
