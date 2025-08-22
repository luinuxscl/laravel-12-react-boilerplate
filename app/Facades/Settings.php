<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed get(string $key, mixed $default = null)
 * @method static void set(string $key, mixed $value)
 * @method static void forget(string $key)
 * @method static array all()
 */
class Settings extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'settings';
    }
}
