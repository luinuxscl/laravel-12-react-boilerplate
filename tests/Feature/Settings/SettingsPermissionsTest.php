<?php

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

it('settings index: forbids non-admin and allows admin', function () {
    $normal = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($normal)
        ->getJson('/admin/settings')
        ->assertStatus(403);

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->getJson('/admin/settings')
        ->assertOk();
});

it('settings update: admin allowed, non-admin 403', function () {
    $payload = [
        'key' => 'test.feature',
        'value' => ['enabled' => true, 'ratio' => 0.5],
    ];

    $normal = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($normal)
        ->putJson('/admin/settings', $payload)
        ->assertStatus(403);

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->putJson('/admin/settings', $payload)
        ->assertOk();
});

it('settings destroy: admin allowed 204, non-admin 403', function () {
    $key = 'to.delete.key';

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    // ensure exists by upserting first
    $this->actingAs($admin)
        ->putJson('/admin/settings', ['key' => $key, 'value' => ['exists' => true]])
        ->assertOk();

    // non-admin cannot delete
    $normal = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($normal)
        ->deleteJson('/admin/settings/'.urlencode($key))
        ->assertStatus(403);

    // admin can delete
    $this->actingAs($admin)
        ->deleteJson('/admin/settings/'.urlencode($key))
        ->assertNoContent();
});
