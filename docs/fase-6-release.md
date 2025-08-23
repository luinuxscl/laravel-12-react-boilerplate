# FASE 6 · Empaquetado y Release

## Checklist de Release (Laravel + Inertia/React)

1) __Revisiones previas__
   - [ ] `composer install --no-dev -o`
   - [ ] `npm ci && npm run build`
   - [ ] Variables en `.env` completas (APP_KEY, DB_*, QUEUE_*…)
   - [ ] Migraciones pendientes aplicadas en staging

2) __Modo mantenimiento__
   - [ ] `composer run deploy:down`

3) __Migraciones__
   - [ ] `composer run deploy:migrate`

4) __Caches de la app__
   - [ ] `composer run app:cache`

5) __Colas__
   - [ ] `composer run deploy:queue-restart`

6) __Salir de mantenimiento__
   - [ ] `composer run deploy:up`

7) __Smoke tests__
   - [ ] Visitar `/login`, `/dashboard`, `/admin/users-ui`, `/notifications-ui`
   - [ ] Revisar logs (`storage/logs/laravel.log`) y healthchecks del host

> Atajo: `composer run deploy` ejecuta pasos 2–6 en orden.

## Comandos útiles

```bash
# Construir assets producción
npm run build

# Limpiar cachés de la app
composer run app:clear

# Preparar cachés (config/route/view/event)
composer run app:cache

# Flujo de deploy completo (down → migrate → cache → restart queues → up)
composer run deploy
```

## Notas por hosting

- __Laravel Forge / Servidor propio__
  - Configurar job de queue (supervisor/systemd) apuntando a `php artisan queue:work --tries=3 --backoff=5`.
  - Asegurar node y build de assets en CI o en deploy script.
  - Activar opcache y `php artisan config:cache` en producción.

- __Laravel Vapor__
  - El build de assets se delega al pipeline de Vapor (assets S3 + CloudFront). Ajustar `vapor.yml`.
  - Usar colas SQS y configurar reintentos.

## Variables de entorno mínimas

Ver `.env.example`. Claves relevantes: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `SESSION_DRIVER`, `QUEUE_CONNECTION`, `MAIL_*` si envías correos, `LOG_LEVEL=warning` en prod.

## Changelog

Mantener en el README o releases del repositorio un resumen por fase (features y fixes).
