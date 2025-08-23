# FASE 4 · Admin Users (UI + API)

## Implementado
- API JSON para DataTable (`UsersController@index`):
  - Búsqueda: `search` en `name`/`email`.
  - Orden: `sortBy` (`id|name|email|created_at`), `sortDir` (`asc|desc`).
  - Paginación: `perPage` (1..100), `page` (>=1).
  - Filtros: `role` (Spatie), `created_from`, `created_to` (formato `YYYY-MM-DD`).
  - Validación de request y `created_to >= created_from`.
- Endpoints Admin (protegidos por `auth`, `verified`, `role:Admin`):
  - `GET /admin/users` → list (DataTable)
  - `GET /admin/users/{user}` → detalle
  - `PUT /admin/users/{user}` → update (`name`)
  - `DELETE /admin/users/{user}` → delete (bloquea auto-eliminación)
  - `GET /admin/roles` → listado de roles (para filtros)
  - `GET /admin/users-ui` → página Inertia con DataTable.
- Frontend (Inertia + React):
  - Página `resources/js/pages/admin/users/index.tsx` con:
    - Búsqueda, orden clickable, paginación.
    - Filtros por Rol (cargado desde `/admin/roles`) y Fecha (desde/hasta), botón "Clear".
    - Acciones por fila: View (modal), Edit (modal) y Delete (confirmación), con refetch y toasts.
  - `DataTable` con skeleton de carga y estado vacío.
- Tests (Pest):
  - `tests/Feature/AdminUsersApiTest.php` (estructura y acceso Admin)
  - `tests/Feature/AdminUsersUpdateApiTest.php` (show/update/validación)
  - `tests/Feature/AdminUsersDeleteApiTest.php` (delete + self-protect)
  - `tests/Feature/AdminUsersFiltersApiTest.php` (filtros por rol/fecha + endpoint roles)

## Archivos relevantes
- Backend:
  - `app/Http/Controllers/UsersController.php`
  - `routes/web.php`
- Frontend:
  - `resources/js/pages/admin/users/index.tsx`
  - `resources/js/components/tables/DataTable.tsx`
  - `resources/js/components/ui/Modal.tsx`
  - `resources/js/hooks/useDataTable.ts`, `resources/js/hooks/useToast.tsx`

## Uso rápido
- UI: visitar `/admin/users-ui` autenticado y con rol `Admin`.
- API ejemplo:
  - `GET /admin/users?search=john&role=Editor&created_from=2025-01-01&created_to=2025-12-31&sortBy=created_at&sortDir=desc&perPage=25&page=1`
  - `PUT /admin/users/{id}` body `{ "name": "New Name" }`
  - `DELETE /admin/users/{id}`

## Pendiente / Mejores Prácticas
- UX/Tabla: botón "Reset sort" y persistencia de estado en URL (querystring) para compartir enlaces.
- Filtros extra: verificación de email (verified/unverified); múltiple selección de roles.
- Acciones masivas: bulk select + bulk delete y/o bulk assign role.
- Rendimiento: integrar React Query para cache/refetch y manejo de estados de red.
- Extras: export CSV respetando filtros actuales.
