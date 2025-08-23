<?php

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('prohíbe delete para usuarios sin rol Admin', function () {
    $this->seed(RolesSeeder::class);
    $target = User::factory()->create();
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->deleteJson("/admin/users/{$target->id}")
        ->assertStatus(403);
});

it('impide auto-eliminación por Admin', function () {
    $this->seed(RolesSeeder::class);

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('Admin');

    $this->actingAs($admin)
        ->deleteJson("/admin/users/{$admin->id}")
        ->assertStatus(422)
        ->assertJsonPath('message', 'You cannot delete yourself.');
});

it('permite a Admin eliminar a otro usuario', function () {
    $this->seed(RolesSeeder::class);

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('Admin');

    $target = User::factory()->create();

    $this->actingAs($admin)
        ->deleteJson("/admin/users/{$target->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('users', ['id' => $target->id]);
});
