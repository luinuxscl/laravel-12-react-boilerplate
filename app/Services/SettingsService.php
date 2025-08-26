<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;

class SettingsService
{
    protected string $cachePrefix = 'settings.';

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever($this->cachePrefix.$key, function () use ($key, $default) {
            // En entorno de tests (o durante el arranque temprano), la tabla puede no existir aún.
            if (!Schema::hasTable('settings')) {
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
        Cache::forever($this->cachePrefix.$key, $model->value);
    }

    public function forget(string $key): void
    {
        Cache::forget($this->cachePrefix.$key);
    }

    public function all(): array
    {
        if (!Schema::hasTable('settings')) {
            return [];
        }
        try {
            return Setting::query()->pluck('value', 'key')->toArray();
        } catch (QueryException $e) {
            return [];
        }
    }
}
