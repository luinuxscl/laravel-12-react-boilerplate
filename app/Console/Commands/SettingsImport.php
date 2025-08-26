<?php

namespace App\Console\Commands;

use App\Facades\Settings;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class SettingsImport extends Command
{
    protected $signature = 'settings:import {path : JSON file with key=>value map} {--prefix= : Optional prefix to prepend to keys} {--dry : Dry run, do not persist}';

    protected $description = 'Import multiple settings from a JSON file (object of key=>value).';

    public function handle(Filesystem $fs): int
    {
        $path = (string) $this->argument('path');
        $prefix = (string) ($this->option('prefix') ?? '');
        $dry = (bool) $this->option('dry');

        if (!$fs->exists($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        try {
            $json = json_decode($fs->get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            $this->error('Invalid JSON: ' . $e->getMessage());
            return self::FAILURE;
        }

        if (!is_array($json)) {
            $this->error('JSON root must be an object with key=>value');
            return self::FAILURE;
        }

        $written = [];
        foreach ($json as $key => $value) {
            $fullKey = $prefix !== '' ? rtrim($prefix, '.').'.'.$key : $key;
            $written[$fullKey] = $value;
            if (!$dry) {
                Settings::set($fullKey, $value);
            }
        }

        $this->info($dry ? 'Dry run - no changes written' : 'Settings imported');
        $this->line(json_encode($written, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
