<?php

use App\Facades\Settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('notificación se almacena correctamente', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->post('/notifications/demo')
        ->assertOk();

    expect($user->notifications()->count())->toBe(1);
    expect($user->unreadNotifications()->count())->toBe(1);
});

it('marca todas las notificaciones como leídas', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Crea varias notificaciones
    $this->actingAs($user)->post('/notifications/demo')->assertOk();
    $this->actingAs($user)->post('/notifications/demo')->assertOk();

    expect($user->fresh()->unreadNotifications()->count())->toBe(2);

    // Marca todas como leídas
    $this->actingAs($user)
        ->post('/notifications/read-all')
        ->assertOk();

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

it('configuración se guarda en cache', function () {
    // write
    Settings::set('app.name', ['value' => 'DemoApp']);

    // first read caches
    $val = Settings::get('app.name');
    expect($val)->toBe(['value' => 'DemoApp']);

    // change DB directly and verify cache still returns old until forget
    \App\Models\Setting::query()->where('key', 'app.name')->update(['value' => ['value' => 'Changed']]);
    expect(Settings::get('app.name'))->toBe(['value' => 'DemoApp']);

    // forget and get new value
    Settings::forget('app.name');
    expect(Settings::get('app.name'))->toBe(['value' => 'Changed']);
});
