<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SettingsService
{
    protected string $cachePrefix = 'settings.';

    protected function prefixForCache(): string
    {
        if (config('tenancy.enabled', true)) {
            $tenantId = app(TenantContext::class)->id();
            if ($tenantId) {
                return "tenant.{$tenantId}.{$this->cachePrefix}";
            }
        }
        return $this->cachePrefix;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $prefix = $this->prefixForCache();
        return Cache::rememberForever($prefix.$key, function () use ($key, $default) {
            // En entorno de tests (o durante el arranque temprano), la tabla puede no existir aún.
            if (! Schema::hasTable('settings')) {
                return $default;
            }
            try {
                $record = Setting::query()->where('key', $key)->first();

                return $record?->value ?? $default;
            } catch (QueryException $e) {
                // Si ocurre por migraciones aún no aplicadas, devolvemos el valor por defecto.
                return $default;
            }
        });
    }

    public function set(string $key, mixed $value): void
    {
        $model = Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
        $prefix = $this->prefixForCache();
        Cache::forever($prefix.$key, $model->value);
    }

    public function forget(string $key): void
    {
        Cache::forget($this->prefixForCache().$key);
    }

    public function all(): array
    {
        if (! Schema::hasTable('settings')) {
            return [];
        }
        try {
            return Setting::query()->pluck('value', 'key')->toArray();
        } catch (QueryException $e) {
            return [];
        }
    }
}
