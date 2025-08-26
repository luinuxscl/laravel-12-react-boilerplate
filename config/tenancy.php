<?php

return [
    'enabled' => env('TENANCY_ENABLED', true),

    // Permitir el header X-Tenant (útil en dev o herramientas internas)
    'allow_header' => env('TENANCY_ALLOW_HEADER', true),

    // Rutas a ignorar por el middleware de tenant (patrones estilo Str::is)
    'ignore_paths' => [
        '/up',
        '/login',
        '/register',
        '/forgot-password',
        '/reset-password*',
        '/email/*',
        '/verification*',
        '/logout',
        '/assets/*',
        '/storage/*',
    ],
];
