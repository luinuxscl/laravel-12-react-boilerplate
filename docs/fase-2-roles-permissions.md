# FASE 2 · Roles y Permisos (Spatie)

## Implementado
- Spatie Laravel Permission integrado y configurado.
- `User` con trait `HasRoles` (`app/Models/User.php`).
- Middlewares alias registrados en `bootstrap/app.php`.
- Seeder de roles base: Super Admin, Admin, Editor, User (`database/seeders/RolesSeeder.php`).
- Rutas protegidas con `role:Admin`.
- Política `UserPolicy` (si aplica en tu versión) y ejemplos de autorización.

## Rutas/Archivos relevantes
- `app/Models/User.php`
- `bootstrap/app.php` (aliases middleware Spatie)
- `database/seeders/RolesSeeder.php`
- `routes/web.php` (rutas admin)

## Pendiente / Mejores Prácticas
- Estándar de nombres y jerarquía de roles.
- Semillas adicionales de permisos específicos por módulo (si se agregan más features).
- Documentar proceso de asignar rol por consola (`php artisan user:create --role=Admin`).
