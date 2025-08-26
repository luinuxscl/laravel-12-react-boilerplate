<?php

namespace App\Providers;

use App\Facades\Settings;
use App\Services\SettingsService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Settings service singleton binding
        $this->app->singleton('settings', function () {
            return new SettingsService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Compartir settings mínimos necesarios para branding/appearance
        $siteName = Settings::get('site.name', config('app.name'));
        $appearance = Settings::get('site.appearance', ['theme' => 'system']);
        $brand = Settings::get('site.brand', [
            'logo_url' => null,
            'favicon_url' => null,
        ]);

        Inertia::share('app', [
            'name' => $siteName,
            'appearance' => $appearance,
            'brand' => $brand,
        ]);

        // También para Blade (layout base)
        View::share([
            'appearance' => $appearance['theme'] ?? 'system',
            'faviconUrl' => $brand['favicon_url'] ?? null,
            'siteName' => $siteName,
        ]);
    }
}
