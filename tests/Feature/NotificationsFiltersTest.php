<?php

use App\Models\User;
use App\Notifications\DemoNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('filtra notificaciones con allOnlyUnread y q', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    // Crear varias notificaciones de demo
    $user->notify(new DemoNotification);
    $user->notify(new DemoNotification);
    $user->notify(new DemoNotification);

    // Marcar una como leída
    $first = $user->notifications()->first();
    $first->markAsRead();

    // Sin filtros: debe devolver unread y all paginados
    $resp = $this->actingAs($user)->getJson('/notifications?perPage=2');
    $resp->assertOk()
        ->assertJsonStructure([
            'unread' => ['data', 'current_page', 'last_page', 'per_page', 'total'],
            'all' => ['data', 'current_page', 'last_page', 'per_page', 'total'],
        ]);

    $json = $resp->json();
    expect($json['unread']['total'])->toBe(2); // 3 totales, 1 leída => 2 unread
    expect($json['all']['total'])->toBe(3);

    // Filtro allOnlyUnread: la lista "all" debe excluir las leídas
    $resp2 = $this->actingAs($user)->getJson('/notifications?perPage=10&allOnlyUnread=1');
    $resp2->assertOk();
    $json2 = $resp2->json();
    expect($json2['all']['total'])->toBe(2);
    foreach ($json2['all']['data'] as $n) {
        expect($n['read_at'])->toBeNull();
    }

    // Filtro q por tipo (DemoNotification en el campo type)
    $resp3 = $this->actingAs($user)->getJson('/notifications?perPage=10&q=DemoNotification');
    $resp3->assertOk();
    $json3 = $resp3->json();
    // Todas son del mismo tipo, deben coincidir 3
    expect($json3['all']['total'])->toBe(3);
});
