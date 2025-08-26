<?php

namespace App\Console\Commands;

use App\Facades\Settings;
use Illuminate\Console\Command;

class SettingsSet extends Command
{
    protected $signature = 'settings:set {key} {value} {--json : Interpret value as JSON}';

    protected $description = 'Set a single settings key to a value. Use --json to decode JSON values.';

    public function handle(): int
    {
        $key = (string) $this->argument('key');
        $value = (string) $this->argument('value');
        $isJson = (bool) $this->option('json');

        if ($isJson) {
            try {
                $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                $value = $decoded;
            } catch (\Throwable $e) {
                $this->error('Invalid JSON: '.$e->getMessage());

                return self::FAILURE;
            }
        }

        Settings::set($key, $value);

        $this->info("Settings updated: {$key}");
        $this->line(json_encode([
            'key' => $key,
            'value' => Settings::get($key),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
