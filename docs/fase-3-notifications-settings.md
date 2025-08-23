# FASE 3 · Notificaciones y Settings

## Implementado
- **Notificaciones (canal database)**:
  - `App/Notifications/DemoNotification.php` (sin cola para entorno de pruebas).
  - `NotificationsController` con endpoints: listar, crear demo, marcar como leída.
  - Rutas protegidas en `routes/web.php`.
- **Settings con caché y Facade**:
  - Modelo: `app/Models/Setting.php` (valor JSON casteado).
  - Servicio: `app/Services/SettingsService.php` (get/set/forget/all) con `Cache::rememberForever`.
  - Facade: `app/Facades/Settings.php`.
  - Binding: `app/Providers/AppServiceProvider.php` (singleton `settings`).
  - Migración: `database/migrations/*_create_settings_table.php`.

## Rutas/Archivos relevantes
- `app/Notifications/DemoNotification.php`
- `app/Http/Controllers/NotificationsController.php`
- `routes/web.php`
- `app/Models/Setting.php`, `app/Services/SettingsService.php`, `app/Facades/Settings.php`, `app/Providers/AppServiceProvider.php`

## Tests
- `tests/Feature/NotificationsAndSettingsTest.php`: persiste notificación en DB y valida cache/overrides de Settings.

## Pendiente / Mejores Prácticas
- Invalidación de cache por etiqueta (si se escalara el módulo).
- Panel UI para editar settings (con validación de esquema por clave).
- Posibilidad de seed inicial de settings críticos (con valores por entorno).
