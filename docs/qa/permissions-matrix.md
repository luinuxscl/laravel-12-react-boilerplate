# Admin Permissions Matrix and Critical Flows

Objetivo: establecer una matriz canónica de permisos por rol (root/admin/user) y validar flujos críticos del área Admin.

Este documento sirve como base para pruebas de Feature y E2E, y para auditoría de seguridad.

## Roles y convenciones

- Roles principales: root, admin, user.
- Códigos esperados:
  - 200/204: permitido.
  - 302: redirección (p.ej., no autenticado o verificación email).
  - 403: prohibido por autorización.
  - 422: validación/edge case (p.ej., self-delete invalidado).
- Las rutas de Admin están anidadas bajo middleware `auth`, `verified` y macro `role:admin|root` con `throttle:admin` y `ajax` para mutaciones.

## Endpoints y permisos (resumen)

Fuentes: `routes/web.php`, `routes/settings.php`.

- Admin area macro: `role:admin|root` + permisos finos (Spatie)
  - Audit Logs
    - GET `admin/audit-logs` → root/admin (200)
  - Users
    - GET `admin/users` (permission: `users.view`) → admin/root (200), user (403)
    - GET `admin/users/{user}` (permission: `users.view`) → admin/root (200), user (403)
    - PUT `admin/users/{user}` (permission: `users.manage`) → admin/root (200/204), user (403)
    - DELETE `admin/users/{user}` (permission: `users.manage`) → admin/root (200/204), user (403)
  - Roles
    - GET `admin/roles` (permission: `roles.view`) → admin/root (200), user (403)
    - POST `admin/roles` (permission: `roles.manage`) → admin/root (201/204), user (403)
    - PUT `admin/roles/{role}` (permission: `roles.manage`) → admin/root (200/204), user (403)
    - DELETE `admin/roles/{role}` (permission: `roles.manage`) → admin/root (200/204), user (403)
  - Settings
    - GET `admin/settings` (permission: `settings.view`) → admin/root (200), user (403)
    - PUT `admin/settings` (permission: `settings.manage`) → admin/root (200/204), user (403)
    - DELETE `admin/settings/{key}` (permission: `settings.manage`) → admin/root (200/204), user (403)
  - Branding uploads
    - POST `admin/branding/logo` (permission: `settings.manage`) → admin/root (200/204), user (403)
    - POST `admin/branding/favicon` (permission: `settings.manage`) → admin/root (200/204), user (403)
  - Admin UI pages (solo acceso, sin permisos finos excepto donde se indica)
    - GET `admin/users-ui` → admin/root (200), user (403)
    - GET `admin/roles-ui` → admin/root (200), user (403)
    - GET `admin/settings-ui` → admin/root (200), user (403)
    - GET `admin/branding-ui` (permission: `settings.manage`) → admin/root (200), user (403)
  - Ejemplo de ruta demo
    - GET `admin-only` (middleware: `role:admin|root`) → admin/root (200), user (403)

- Notificaciones (no admin)
  - GET `notifications`, POST `notifications/*` → bajo `auth`/`verified` (no parte de admin), validar según diseño.

- Settings personales (no admin): `routes/settings.php`
  - GET/PATCH/DELETE `settings/profile` → propietario autenticado.
  - GET/PUT `settings/password` → propietario autenticado; `PUT` con `throttle:6,1`.
  - GET `settings/appearance` → autenticado.

## Matriz tipo (root/admin/user)

| Área        | Acción/Endpoint                      | Permiso fino          | root | admin | user |
|-------------|--------------------------------------|-----------------------|------|-------|------|
| Users       | GET admin/users                       | users.view            | 200  | 200   | 403  |
| Users       | PUT admin/users/{user}                | users.manage          | 204  | 204   | 403  |
| Users       | DELETE admin/users/{user}             | users.manage          | 204  | 204   | 403  |
| Roles       | GET admin/roles                       | roles.view            | 200  | 200   | 403  |
| Roles       | POST admin/roles                      | roles.manage          | 201  | 201   | 403  |
| Roles       | PUT admin/roles/{role}                | roles.manage          | 204  | 204   | 403  |
| Roles       | DELETE admin/roles/{role}             | roles.manage          | 204  | 204   | 403  |
| Settings    | GET admin/settings                    | settings.view         | 200  | 200   | 403  |
| Settings    | PUT admin/settings                    | settings.manage       | 204  | 204   | 403  |
| Settings    | DELETE admin/settings/{key}           | settings.manage       | 204  | 204   | 403  |
| Branding    | POST admin/branding/logo              | settings.manage       | 204  | 204   | 403  |
| Branding    | POST admin/branding/favicon           | settings.manage       | 204  | 204   | 403  |
| Audit Logs  | GET admin/audit-logs                  | (macro admin/root)    | 200  | 200   | 403  |
| UI          | GET admin/users-ui                    | (macro admin/root)    | 200  | 200   | 403  |
| UI          | GET admin/roles-ui                    | (macro admin/root)    | 200  | 200   | 403  |
| UI          | GET admin/settings-ui                 | (macro admin/root)    | 200  | 200   | 403  |
| UI          | GET admin/branding-ui                 | settings.manage       | 200  | 200   | 403  |
| Demo        | GET admin-only                        | (macro admin/root)    | 200  | 200   | 403  |

Notas:
- Donde aplique, considerar `verified` para acceso a dashboard/UI.
- Para mutaciones, el middleware `ajax` exige cabecera X-Requested-With.

## Casos edge y validaciones sugeridas

- Root-only sensibles: confirmar que acciones de gestión de roles y settings críticos no puedan ser realizadas por `admin` si existe distinción `roles.manage_root` (si se define). Si no existe, evaluar si se requiere.
- Self-delete: un usuario autenticado no debería poder eliminarse a sí mismo desde Admin (esperar 422/403 según regla). Agregar cobertura.
- Forzar acción desde cliente: confirmar que Policies/permissions bloqueen (403) aunque la UI se intente saltar.
- Rate limiting admin: validar comportamiento de `throttle:admin` con ráfagas de mutaciones.

## Plan de verificación (Feature + E2E)

1. Feature tests
   - Cobertura por endpoint con usuarios seed: root, admin, user.
   - Verificar 200/204 vs 403.
2. E2E (issue #5)
   - Login, navegación a UI admin y visibilidad condicional.
   - Acciones bloqueadas muestran feedback (403) y UI coherente (botones deshabilitados/ocultos).

## Evidencia y resultados

- Registrar resultados de ejecución (fecha, commit) y anexar capturas/logs cuando aplique.
- Issues derivados: enumerar y linkear si se detectan gaps.
