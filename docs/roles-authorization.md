# Roles y Autorización (root vs admin)

Este documento resume las reglas de autorización, endpoints y pautas de UI implementadas para separar estrictamente la gestión de `root` y `admin`.

## Principios
- `root` es reservado. Solo usuarios con rol `root` pueden gestionar usuarios o el rol `root`.
- `admin` puede gestionar usuarios y roles comunes, pero nunca `root`.
- Comparaciones de nombres de roles son insensibles a mayúsculas/minúsculas ("Root", "ROOT" ⇒ `root`).
- La UI refleja los permisos (oculta/deshabilita acciones sensibles), pero la protección real está en el backend (Policies/Gates).

## Backend

### Policies
- `app/Policies/UserPolicy.php`:
  - `viewAny`, `view`, `update`, `delete`:
    - Solo `root` puede operar sobre usuarios `root`.
    - `admin` y `root` pueden operar sobre usuarios no-root.
    - Un usuario siempre puede `view` su propio perfil.

### Gates
- `app/Providers/AuthServiceProvider.php`:
  - `manage-roles`: para `admin` y `root` (roles no-root).
  - `assign-root`: exclusivo para `root` (crear/renombrar/eliminar rol `root`).
  - Validaciones insensibles a mayúsculas.

### Controladores
- `app/Http/Controllers/RolesController.php`:
  - `store`, `update`, `destroy`: si el objetivo o el nuevo nombre es `root` ⇒ `Gate::authorize('assign-root')`; si no, `manage-roles`.
  - Comparaciones a minúsculas (`strtolower(...) === 'root'`).
- `app/Http/Controllers/UsersController.php`:
  - Usa `authorize()` con `UserPolicy` para `show`, `update`, `destroy`.
  - `index()` y `show()` hacen eager load de `roles` para exponer flags a la UI.

### Resources
- `app/Http/Resources/UserResource.php`:
  - Campos extra:
    - `roles`: lista de roles cuando están cargados (`whenLoaded`).
    - `is_root`: boolean calculado.

### Rutas
- `routes/web.php`:
  - Rutas de administración bajo `middleware('role:Admin')`.
  - UI: `/admin/users-ui`, `/admin/roles-ui`.
  - API JSON: `/admin/users`, `/admin/roles` (CRUD).

## Frontend (Inertia + React)
- `app/Http/Middleware/HandleInertiaRequests.php`: comparte `auth.roles`, `auth.isAdmin`, `auth.isRoot`.
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
- `tests/Feature/Roles/RolesControllerTest.php`: CRUD de roles comunes.
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

## Próximos pasos sugeridos
- Revisar otras áreas (Settings, Notifications) para aplicar el mismo patrón de autorización y UI.
- Mantener una guía de contribución sobre roles y permisos para nuevos devs.
