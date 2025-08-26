# Multitenancy PoC

Este documento resume cómo funciona el PoC de multitenancy en este boilerplate y cómo usarlo durante desarrollo y pruebas.

## Conceptos clave

- El tenant actual se resuelve a partir del header HTTP `X-Tenant`, cuyo valor es el `slug` del tenant.
- El backend filtra recursos por `tenant_id` y bloquea accesos cruzados con `404`.
- Para la UI de Usuarios, el frontend consume `/admin/users` y `/admin/roles` con filtros, sort y paginación.

## Cabecera requerida

- Envía `X-Tenant: <slug>` en todas las requests a rutas admin que dependan del tenant.
- En tests de Feature, esto se hace con `->withHeaders(['X-Tenant' => $tenant->slug])`.

## Resolución de tenant (backend)

- Servicio: `app/Services/TenantResolver.php`
- Orden de resolución:
  1. Header `X-Tenant` (útil en desarrollo y cuando `tenancy.allow_header=true`).
  2. Dominio exacto (`tenant.domain`).
  3. Subdominio `{slug}.domain.tld`.
  4. Fallback por usuario autenticado: si hay un usuario con `tenant_id`, usa ese tenant.
  5. Fallback final: tenant por defecto (`is_default = true`).

Este fallback por usuario autenticado permite que, en desarrollo con host raíz (sin subdominio) y sin header, se resuelva el tenant correcto del usuario logueado.

## Datos y seeds

- Roles se inicializan con `Database\Seeders\RolesSeeder` (`admin`, `root`, etc.).
- A la hora de crear usuarios en tests o seeders, asigna siempre `tenant_id`.

## Endpoints relevantes

- `GET /admin/users` – lista usuarios del tenant con filtros: `search`, `role`, `created_from`, `created_to`, `sortBy`, `sortDir`, `perPage`.
- `GET /admin/users/{id}` – detalle de un usuario (aislado por tenant).
- `PUT /admin/users/{id}` – actualizar `name` (restricciones: no gestionar root salvo `root`).
- `DELETE /admin/users/{id}` – eliminar (bloquea auto-eliminación y no permite gestionar root salvo `root`).
- `GET /admin/roles` – lista de roles (para filtros en UI).

## Frontend (React)

Archivo: `resources/js/pages/admin/users/index.tsx`

- El token CSRF se lee dentro del componente con `useMemo`.
- Se valida `Content-Type` antes de parsear JSON en todas las llamadas `fetch` (listado, update, delete, refetchs).
- La columna `actions` no es ordenable.
- Las filas usan claves estables vía `rowKey="id"` en `DataTable`.

### Envío automático de X-Tenant

- El layout comparte `tenant` en props de Inertia (ver `AppServiceProvider` → `Inertia::share('tenant', ...)`).
- En `resources/js/pages/admin/users/index.tsx` se lee `tenant.slug` y se construye `baseHeaders` con `X-Tenant` para todas las llamadas `fetch` (listado, roles, update, delete y refetchs).

Componente `DataTable`: `resources/js/components/tables/DataTable.tsx`

- Acepta `rowKey?: keyof T | (row, index) => string | number` para claves estables de filas.

## Ejecución de pruebas

- Ejecutar suite completa:

```bash
composer test
```

- Pruebas de aislamiento por tenant:
  - `tests/Feature/Tenancy/UsersTenantIsolationTest.php`
  - `tests/Feature/Tenancy/SettingsTenantIsolationTest.php`

Todas las pruebas deben pasar. La suite valida:
- Aislamiento de usuarios y settings por tenant.
- Estructura JSON de `UserResource` (incluye `tenant_id`).
- Políticas de acceso (admin vs no admin, root restrictions).

## Consideraciones de diseño

- Índice único en settings: `UNIQUE(tenant_id, key)` para permitir la misma `key` en distintos tenants.
- Nombres y código en inglés; comentarios en español solo donde agregan contexto.
- Mantener simple la lógica (KISS) y cohesionada (SRP). Evitar complejidad innecesaria.

## Troubleshooting

- 404 inesperados en admin/users: frecuentemente falta el header `X-Tenant` o el `tenant_id` del modelo no coincide con el tenant actual.
- Errores de parseo JSON: revisar `Content-Type` del response y los checks añadidos en `fetch`.
