<?php

namespace App\Providers;

use App\Facades\Settings;
use App\Support\TenantContext;
use App\Services\SettingsService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        // Tenant context per-request singleton
        $this->app->singleton(TenantContext::class, function () {
            return new TenantContext();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Rate limiter para endpoints administrativos
        RateLimiter::for('admin', function (Request $request) {
            // Limitar por usuario autenticado si existe; si no, por IP
            $key = optional($request->user())->getAuthIdentifier() ?? $request->ip();
            return Limit::perMinute(60)->by($key);
        });

        // Compartir settings y tenant por-request mediante closures
        Inertia::share('tenant', function () {
            $tenant = app(TenantContext::class)->get();
            if (! $tenant) {
                return null;
            }
            return [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'domain' => $tenant->domain,
                'is_default' => $tenant->is_default,
            ];
        });

        Inertia::share('app', function () {
            $siteName = Settings::get('site.name', config('app.name'));
            $appearance = Settings::get('site.appearance', ['theme' => 'system']);
            $brand = Settings::get('site.brand', [
                'logo_url' => null,
                'favicon_url' => null,
            ]);

            return [
                'name' => $siteName,
                'appearance' => $appearance,
                'brand' => $brand,
            ];
        });

        // También para Blade (layout base) vía composer para evaluar por request
        View::composer('*', function ($view) {
            $appearance = Settings::get('site.appearance', ['theme' => 'system']);
            $brand = Settings::get('site.brand', [
                'logo_url' => null,
                'favicon_url' => null,
            ]);
            $siteName = Settings::get('site.name', config('app.name'));

            $view->with([
                'appearance' => $appearance['theme'] ?? 'system',
                'faviconUrl' => $brand['favicon_url'] ?? null,
                'siteName' => $siteName,
            ]);
        });
    }
}
