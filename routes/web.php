<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\UsersController;
use Spatie\Permission\Models\Role;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Ruta de ejemplo protegida por rol Admin
    Route::get('admin-only', function () {
        return response('OK', 200);
    })->middleware('role:Admin')->name('admin.only');

    // Notificaciones (DB)
    Route::get('notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::post('notifications/demo', [NotificationsController::class, 'demo'])->name('notifications.demo');
    Route::post('notifications/{id}/read', [NotificationsController::class, 'markAsRead'])->name('notifications.read');

    // DataTable de usuarios (solo Admin)
    Route::get('admin/users', [UsersController::class, 'index'])
        ->middleware('role:Admin')
        ->name('admin.users.index');

    // Listado de roles (solo Admin)
    Route::get('admin/roles', function () {
        return response()->json([
            'data' => Role::query()->orderBy('name')->pluck('name'),
        ]);
    })->middleware('role:Admin')->name('admin.roles.index');

    // Detalle y actualización de usuario (solo Admin)
    Route::get('admin/users/{user}', [UsersController::class, 'show'])
        ->middleware('role:Admin')
        ->name('admin.users.show');
    Route::put('admin/users/{user}', [UsersController::class, 'update'])
        ->middleware('role:Admin')
        ->name('admin.users.update');
    Route::delete('admin/users/{user}', [UsersController::class, 'destroy'])
        ->middleware('role:Admin')
        ->name('admin.users.destroy');

    // Página UI para DataTable de usuarios (solo Admin)
    Route::get('admin/users-ui', function () {
        return Inertia::render('admin/users/index');
    })->middleware('role:Admin')->name('admin.users.ui');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
