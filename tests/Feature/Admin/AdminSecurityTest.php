<?php

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('enforces rate limiting on admin routes (429)', function () {
    $this->seed(RolesSeeder::class);
    $tenant = Tenant::query()->create(['name' => 'Demo', 'slug' => 'demo', 'is_default' => true]);
    $admin = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $admin->assignRole('admin');

    // Hit an admin GET route repeatedly until limit reached (60/min default)
    for ($i = 0; $i < 60; $i++) {
        $this->actingAs($admin)
            ->withHeaders(['X-Tenant' => $tenant->slug])
            ->get('/admin/users');
    }

    // Next request should be throttled
    $this->actingAs($admin)
        ->withHeaders(['X-Tenant' => $tenant->slug])
        ->get('/admin/users')
        ->assertStatus(429);
});

it('requires X-Requested-With for non-JSON mutating admin requests (403)', function () {
    $this->seed(RolesSeeder::class);
    $tenant = Tenant::query()->create(['name' => 'Demo', 'slug' => 'demo', 'is_default' => true]);
    $admin = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $admin->assignRole('admin');

    $target = User::factory()->create(['tenant_id' => $tenant->id]);

    // Iniciar sesión/CSRF
    $this->get('/');

    // PUT sin JSON y sin X-Requested-With, pero con CSRF válido
    $this->actingAs($admin)
        ->withHeaders(['X-Tenant' => $tenant->slug])
        ->put("/admin/users/{$target->id}", [
            '_token' => csrf_token(),
            'name' => 'Should Fail',
        ])
        ->assertStatus(403);

    // Mismo request, ahora como AJAX válido
    $this->actingAs($admin)
        ->withHeaders([
            'X-Tenant' => $tenant->slug,
            'X-Requested-With' => 'XMLHttpRequest',
        ])
        ->put("/admin/users/{$target->id}", [
            '_token' => csrf_token(),
            'name' => 'Ok Name',
        ])
        ->assertStatus(200);
});
