# Roles y Autorización (root vs admin)

Este documento resume las reglas de autorización, endpoints y pautas de UI implementadas para separar estrictamente la gestión de `root` y `admin`.

## Principios
- `root` es reservado. Solo usuarios con rol `root` pueden gestionar usuarios o el rol `root`.
- `admin` puede gestionar usuarios y roles comunes, pero nunca `root`.
- Comparaciones de nombres de roles son insensibles a mayúsculas/minúsculas ("Root", "ROOT" ⇒ `root`).
- La UI refleja los permisos (oculta/deshabilita acciones sensibles), pero la protección real está en el backend (Policies/Gates).

## Backend

### Gate global (root)
- `app/Providers/AuthServiceProvider.php`
  - `Gate::before(...)`: si el usuario tiene rol `root`, se permite cualquier ability/policy automáticamente.
  - Gates específicos:
    - `manage-roles`: para gestión general de roles no-root.
    - `assign-root`: exclusivo de `root` (crear/renombrar/eliminar rol `root`).

### Policies
- `app/Policies/UserPolicy.php`:
  - `viewAny`, `view`, `update`, `delete`:
    - Solo `root` puede operar sobre usuarios `root`.
    - `admin` y `root` pueden operar sobre usuarios no-root.
    - Un usuario siempre puede `view` su propio perfil.
- `app/Policies/RolePolicy.php`:
  - `viewAny`, `view`, `create`, `update`, `delete`:
    - Requiere permisos `roles.view`/`roles.manage`.
    - Si el rol objetivo es `root`, exige `roles.manage_root` (solo `root` lo tiene).

### Controladores
- `app/Http/Controllers/RolesController.php` (refactorizado):
  - Usa `authorize()` para `viewAny/create/update/delete` (Policies).
  - Extra: si en `store/update` el nombre nuevo es `root`, se fuerza `Gate::authorize('assign-root')`.
- `UsersController` (no detallado aquí): recomendado usar `authorize()` con `UserPolicy`.

### Rutas
- `routes/web.php`:
  - Grupo macro: `middleware('role:admin|root')` para el área Admin.
  - Permisos finos por endpoint con `permission:*`:
    - Users: `users.view` (index/show), `users.manage` (update/destroy).
    - Roles: `roles.view` (index), `roles.manage` (store/update/destroy). La Policy cubre `roles.manage_root`.
    - Settings: `settings.view`/`settings.manage`.

## Frontend (Inertia + React)
- `app/Http/Middleware/HandleInertiaRequests.php`: comparte `auth.roles`, `auth.isAdmin`, `auth.isRoot`.
- `resources/js/lib/auth.ts`: helper `makeAuthHelpers()` que deriva capacidades de UI (`canManageUsers`, `canManageRoles`, `canManageSettings`, etc.). La UI lo usa para mostrar/ocultar o deshabilitar acciones, pero siempre se valida en backend.
- `resources/js/components/app-sidebar.tsx`:
  - Grupo “Admin”: visible solo si `isAdmin || isRoot`.
  - Enlace “Roles”: visible solo si `isRoot`.
- `resources/js/pages/admin/users/index.tsx`:
  - Oculta/deshabilita Edit/Delete cuando el target es `root` y el actor no es `root`.
  - Evita auto-eliminación (self-delete) desde la UI.
- `resources/js/pages/admin/roles/index.tsx`:
  - Bloquea crear/editar/eliminar el rol `root` si el actor no es `root`.
  - Botones deshabilitados con tooltip explicativo.
- Páginas de error `403/404/500`: CTAs condicionales por rol.

## Tests
- `tests/Feature/Roles/RootRestrictionsTest.php`:
  - Admin no puede crear/renombrar/eliminar `root`.
  - Root sí puede (contempla colisiones 422 por nombre duplicado).
- `tests/Feature/Roles/RolesControllerTest.php`: CRUD de roles comunes (helper asigna permisos `roles.view/manage`).
- `tests/Feature/Auth/RootPolicyTest.php`: reglas `UserPolicy` root/admin.
- `tests/Feature/AdminUsers*`: filtros por rol, visibilidad y acciones.

## Buenas prácticas
- Siempre validar autorización en servidor (Policies/Gates) aunque la UI oculte acciones.
- Eager load de relaciones necesarias (`with('roles')`) para evitar N+1.
- Mantener los nombres de roles consistentes; comparar con `strtolower()` si aplica.
- Añadir tooltips/feedback cuando una acción esté deshabilitada por permisos.

## Endpoints relevantes
- Roles:
  - `GET /admin/roles`
  - `POST /admin/roles` (crear)
  - `PUT /admin/roles/{role}` (renombrar)
  - `DELETE /admin/roles/{role}` (eliminar)
- Users:
  - `GET /admin/users` (DataTable JSON)
  - `GET /admin/users/{user}`
  - `PUT /admin/users/{user}`
  - `DELETE /admin/users/{user}`

## Provisión de usuario inicial (ops)
- Comando Artisan para crear/actualizar un usuario inicial con rol `admin` o `root` de forma idempotente:
  - Archivo: `app/Console/Commands/ProvisionInitialUser.php`
  - Firma: `php artisan user:provision --email=... --name=... --password=... --role=admin|root [--verified] [--yes]`
  - En producción solicita confirmación, a menos que se use `--yes`.

Ejemplos:

```bash
# Admin inicial
php artisan user:provision \
  --email=admin@example.com \
  --name="Initial Admin" \
  --password='SuperSecret123' \
  --role=admin \
  --verified \
  --yes

# Root inicial (precaución: acceso total)
php artisan user:provision \
  --email=root@example.com \
  --name="Root" \
  --password='AnotherSecret!' \
  --role=root \
  --verified \
  --yes
```

Notas:
- El comando asegura la existencia de roles/permisos base ejecutando `RolesSeeder` si es necesario.
- No imprime la contraseña tras crear; guárdala de forma segura.

## Próximos pasos sugeridos
- Revisar otras áreas (Settings, Notifications) para aplicar el mismo patrón de autorización y UI.
- Mantener una guía de contribución sobre roles y permisos para nuevos devs.
