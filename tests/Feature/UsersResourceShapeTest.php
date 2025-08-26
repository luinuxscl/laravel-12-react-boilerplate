<?php

use App\Models\User;
use App\Models\Tenant;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('UsersController show devuelve UserResource shape', function () {
    $this->seed(RolesSeeder::class);
    $tenant = Tenant::query()->create(['name' => 'Demo', 'slug' => 'demo', 'is_default' => true]);
    $admin = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $admin->assignRole('admin');

    $target = User::factory()->create(['name' => 'Target User', 'tenant_id' => $tenant->id]);

    $resp = $this->actingAs($admin)->withHeaders(['X-Tenant' => $tenant->slug])->getJson("/admin/users/{$target->id}");
    $resp->assertOk()
        ->assertJsonStructure([
            'data' => ['id', 'name', 'email', 'email_verified_at', 'created_at', 'updated_at'],
        ])
        ->assertJsonPath('data.id', $target->id)
        ->assertJsonPath('data.name', 'Target User');
});

it('UsersController update devuelve UserResource shape', function () {
    $this->seed(RolesSeeder::class);
    $tenant = Tenant::query()->create(['name' => 'Demo', 'slug' => 'demo', 'is_default' => true]);
    $admin = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $admin->assignRole('admin');

    $target = User::factory()->create(['name' => 'Old', 'tenant_id' => $tenant->id]);

    $resp = $this->actingAs($admin)->withHeaders(['X-Tenant' => $tenant->slug])->putJson("/admin/users/{$target->id}", ['name' => 'New']);
    $resp->assertOk()
        ->assertJsonStructure([
            'data' => ['id', 'name', 'email', 'email_verified_at', 'created_at', 'updated_at'],
        ])
        ->assertJsonPath('data.name', 'New');
});
