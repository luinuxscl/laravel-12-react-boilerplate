# Admin security hardening

Este documento describe los mecanismos aplicados para endurecer la seguridad del área administrativa (admin).

## Rate limiting
- Limiter: `admin` definido en `app/Providers/AppServiceProvider.php`.
- Regla por defecto: `60` requests por minuto, con clave por usuario autenticado (`user_id`) o `ip` si no hay sesión.
- Aplicación: grupo de rutas admin en `routes/web.php` usa `throttle:admin`.

Cómo ajustar el límite:
```php
// app/Providers/AppServiceProvider.php
RateLimiter::for('admin', function (Request $request) {
    $key = optional($request->user())->getAuthIdentifier() ?? $request->ip();
    return Limit::perMinute(60)->by($key); // <-- cambia 60 por el valor deseado
});
```

## Requisito de header AJAX en mutaciones
- Middleware: `App\Http\Middleware\EnforceAjax` (alias `ajax`, registrado en `bootstrap/app.php`).
- Alcance: aplicado al grupo admin junto con `role:admin|root` y `throttle:admin`.
- Comportamiento:
  - Para `POST/PUT/PATCH/DELETE` no-JSON, exige `X-Requested-With: XMLHttpRequest`.
  - Para solicitudes JSON (tests/API), no requiere el header.
  - Si falta el header en una mutación no-JSON: respuesta `403`.

## CSRF
- Las rutas admin se ejecutan bajo el stack `web`, por lo que la verificación CSRF está activa.
- No existen exclusiones personalizadas para endpoints admin.

## Tests
- Archivo: `tests/Feature/Admin/AdminSecurityTest.php`.
- Casos cubiertos:
  - 429 cuando se alcanza el rate limit de rutas admin.
  - 403 cuando falta `X-Requested-With` en una mutación no-JSON.

## Rutas afectadas
- El grupo admin en `routes/web.php` incluye los controladores `UsersController`, `RolesController`, `Admin\SettingsController`, y endpoints de branding.

## Notas operativas
- Si tu UI envía formularios clásicos (no fetch/AJAX) para mutaciones admin, asegúrate de enviar el header `X-Requested-With: XMLHttpRequest` o usar peticiones JSON.
- Para depurar respuestas 429 en ambientes de staging, ajusta temporalmente el límite o usa claves diferenciadas (usuario vs IP) según el caso.
