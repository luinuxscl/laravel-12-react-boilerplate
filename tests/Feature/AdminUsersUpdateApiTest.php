<?php

use App\Models\User;
use App\Models\Tenant;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('prohíbe show/update para usuarios sin rol Admin', function () {
    $this->seed(RolesSeeder::class);
    $tenant = Tenant::query()->create(['name' => 'Demo', 'slug' => 'demo', 'is_default' => true]);
    $target = User::factory()->create(['tenant_id' => $tenant->id]);
    $user = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);

    $this->actingAs($user)->withHeaders(['X-Tenant' => $tenant->slug])
        ->getJson("/admin/users/{$target->id}")
        ->assertStatus(403);

    $this->actingAs($user)->withHeaders(['X-Tenant' => $tenant->slug])
        ->putJson("/admin/users/{$target->id}", ['name' => 'New Name'])
        ->assertStatus(403);
});

it('permite a Admin ver y actualizar un usuario', function () {
    $this->seed(RolesSeeder::class);
    $tenant = Tenant::query()->create(['name' => 'Demo', 'slug' => 'demo', 'is_default' => true]);
    $admin = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $admin->assignRole('admin');

    $target = User::factory()->create(['name' => 'Old Name', 'tenant_id' => $tenant->id]);

    $this->actingAs($admin)->withHeaders(['X-Tenant' => $tenant->slug])
        ->getJson("/admin/users/{$target->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $target->id);

    $this->actingAs($admin)->withHeaders(['X-Tenant' => $tenant->slug])
        ->putJson("/admin/users/{$target->id}", ['name' => 'New Name'])
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name');
});

it('valida nombre requerido al actualizar', function () {
    $this->seed(RolesSeeder::class);
    $tenant = Tenant::query()->create(['name' => 'Demo', 'slug' => 'demo', 'is_default' => true]);
    $admin = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $admin->assignRole('admin');

    $target = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($admin)->withHeaders(['X-Tenant' => $tenant->slug])
        ->putJson("/admin/users/{$target->id}", ['name' => ''])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});
