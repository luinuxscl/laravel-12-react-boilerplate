<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
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
    Route::post('notifications/read-all', [NotificationsController::class, 'markAllAsRead'])->name('notifications.read_all');

    // Página UI para Notificaciones
    Route::get('notifications-ui', function () {
        return Inertia::render('notifications/index');
    })->name('notifications.ui');

    // DataTable de usuarios (solo Admin)
    Route::get('admin/users', [UsersController::class, 'index'])
        ->middleware('role:Admin')
        ->name('admin.users.index');

    // Roles CRUD (solo Admin)
    Route::middleware('role:Admin')->group(function () {
        Route::get('admin/roles', [RolesController::class, 'index'])->name('admin.roles.index.json');
        Route::post('admin/roles', [RolesController::class, 'store'])->name('admin.roles.store');
        Route::put('admin/roles/{role}', [RolesController::class, 'update'])->name('admin.roles.update');
        Route::delete('admin/roles/{role}', [RolesController::class, 'destroy'])->name('admin.roles.destroy');
    });

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

    // Página UI para Roles (solo Admin)
    Route::get('admin/roles-ui', function () {
        return Inertia::render('admin/roles/index');
    })->middleware('role:Admin')->name('admin.roles.ui');

    // Admin Settings (simple CRUD)
    Route::middleware('role:Admin')->group(function () {
        Route::get('admin/settings-ui', [AdminSettingsController::class, 'page'])->name('admin.settings.ui');
        Route::get('admin/settings', [AdminSettingsController::class, 'index'])->name('admin.settings.index');
        Route::put('admin/settings', [AdminSettingsController::class, 'update'])->name('admin.settings.update');
        Route::delete('admin/settings/{key}', [AdminSettingsController::class, 'destroy'])->name('admin.settings.destroy');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
