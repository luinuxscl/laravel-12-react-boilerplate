<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    protected string $cachePrefix = 'settings.';

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever($this->cachePrefix.$key, function () use ($key, $default) {
            $record = Setting::query()->where('key', $key)->first();
            return $record?->value ?? $default;
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
        return Setting::query()->pluck('value', 'key')->toArray();
    }
}
