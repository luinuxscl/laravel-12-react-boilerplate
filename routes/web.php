<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
