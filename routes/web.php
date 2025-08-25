<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\BrandingController;
use Spatie\Permission\Models\Role;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Ruta de ejemplo protegida por acceso admin/root
    Route::get('admin-only', function () {
        return response('OK', 200);
    })->middleware('role:admin|root')->name('admin.only');

    // Notificaciones (DB)
    Route::get('notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::post('notifications/demo', [NotificationsController::class, 'demo'])->name('notifications.demo');
    Route::post('notifications/{id}/read', [NotificationsController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationsController::class, 'markAllAsRead'])->name('notifications.read_all');

    // Página UI para Notificaciones
    Route::get('notifications-ui', function () {
        return Inertia::render('notifications/index');
    })->name('notifications.ui');

    // Área Admin (macro): requiere rol admin o root
    Route::middleware('role:admin|root')->group(function () {
        // Users
        Route::get('admin/users', [UsersController::class, 'index'])
            ->middleware('permission:users.view')
            ->name('admin.users.index');
        Route::get('admin/users/{user}', [UsersController::class, 'show'])
            ->middleware('permission:users.view')
            ->name('admin.users.show');
        Route::put('admin/users/{user}', [UsersController::class, 'update'])
            ->middleware('permission:users.manage')
            ->name('admin.users.update');
        Route::delete('admin/users/{user}', [UsersController::class, 'destroy'])
            ->middleware('permission:users.manage')
            ->name('admin.users.destroy');

        // Roles (policy + permisos)
        Route::get('admin/roles', [RolesController::class, 'index'])
            ->middleware('permission:roles.view')
            ->name('admin.roles.index.json');
        Route::post('admin/roles', [RolesController::class, 'store'])
            ->middleware('permission:roles.manage')
            ->name('admin.roles.store');
        Route::put('admin/roles/{role}', [RolesController::class, 'update'])
            ->middleware('permission:roles.manage')
            ->name('admin.roles.update');
        Route::delete('admin/roles/{role}', [RolesController::class, 'destroy'])
            ->middleware('permission:roles.manage')
            ->name('admin.roles.destroy');

        // Settings
        Route::get('admin/settings', [AdminSettingsController::class, 'index'])
            ->middleware('permission:settings.view')
            ->name('admin.settings.index');
        Route::put('admin/settings', [AdminSettingsController::class, 'update'])
            ->middleware('permission:settings.manage')
            ->name('admin.settings.update');
        Route::delete('admin/settings/{key}', [AdminSettingsController::class, 'destroy'])
            ->middleware('permission:settings.manage')
            ->name('admin.settings.destroy');

        // Branding uploads
        Route::post('admin/branding/logo', [BrandingController::class, 'uploadLogo'])
            ->middleware('permission:settings.manage')
            ->name('admin.branding.logo');
        Route::post('admin/branding/favicon', [BrandingController::class, 'uploadFavicon'])
            ->middleware('permission:settings.manage')
            ->name('admin.branding.favicon');

        // Páginas UI (requieren acceso a admin; sin permisos finos)
        Route::get('admin/users-ui', function () {
            return Inertia::render('admin/users/index');
        })->name('admin.users.ui');

        Route::get('admin/roles-ui', function () {
            return Inertia::render('admin/roles/index');
        })->name('admin.roles.ui');

        Route::get('admin/settings-ui', [AdminSettingsController::class, 'page'])
            ->name('admin.settings.ui');
    });

    
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
