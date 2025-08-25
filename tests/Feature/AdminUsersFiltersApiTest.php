<?php

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('lista roles para admin', function () {
    $this->seed(RolesSeeder::class);
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->getJson('/admin/roles')
        ->assertOk()
        ->assertJsonStructure(['data'])
        ->assertJson(fn ($json) => $json->has('data')->etc());
});

it('filtra usuarios por rol y rango de fechas', function () {
    $this->seed(RolesSeeder::class);
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    // Crear usuarios con distintos roles
    Role::findOrCreate('Editor', 'web');
    $u1 = User::factory()->create(['created_at' => now()->subDays(10)]); // sin rol
    $u2 = User::factory()->create(['created_at' => now()->subDays(5)]);  // Editor
    $u2->assignRole('Editor');
    $u3 = User::factory()->create(['created_at' => now()->subDays(1)]);  // Editor
    $u3->assignRole('Editor');

    // Filtro por rol
    $r1 = $this->actingAs($admin)->getJson('/admin/users?role=Editor');
    $r1->assertOk();
    $ids = collect($r1->json('data'))->pluck('id');
    expect($ids)->toContain($u2->id, $u3->id)->not->toContain($u1->id);

    // Filtro por fechas: últimos 3 días
    $from = now()->subDays(3)->toDateString();
    $to = now()->toDateString();
    $r2 = $this->actingAs($admin)->getJson("/admin/users?created_from={$from}&created_to={$to}");
    $r2->assertOk();
    $ids2 = collect($r2->json('data'))->pluck('id');
    expect($ids2)->toContain($u3->id)->not->toContain($u1->id);
});
