# Laravel 12 + React Boilerplate (Extended)

Proyecto base extendido con roles y permisos (Spatie), notificaciones, settings con caché, API y UI de administración de usuarios (DataTable con búsqueda/orden/paginación/filtros), y utilidades CLI.

## Índice de Documentación

La documentación detallada por fases está en `docs/`:

- FASE 1: Setup Inicial → [docs/fase-1-setup.md](./docs/fase-1-setup.md)
- FASE 2: Roles y Permisos → [docs/fase-2-roles-permissions.md](./docs/fase-2-roles-permissions.md)
- FASE 3: Notificaciones y Settings → [docs/fase-3-notifications-settings.md](./docs/fase-3-notifications-settings.md)
- FASE 4: Admin Users (UI + API) → [docs/fase-4-admin-users-ui.md](./docs/fase-4-admin-users-ui.md)
- FASE 5: DX e Higiene → [docs/fase-5-dx.md](./docs/fase-5-dx.md)
- FASE 6: Empaquetado y Release → [docs/fase-6-release.md](./docs/fase-6-release.md)
 - Multitenancy PoC (aislamiento por tenant, header X-Tenant) → [docs/multitenancy-poc.md](./docs/multitenancy-poc.md)

## Requisitos

- PHP 8.3+
- Composer
- Node 18+
- Base de datos (SQLite/MySQL/PostgreSQL)

## Instalación rápida

```bash
# Dependencias backend y frontend
composer install
npm install

# Copiar variables de entorno y configurar DB
cp .env.example .env
php artisan key:generate
# Ajusta DB_* en .env (o usa SQLite)

# Migraciones y seed básicos
php artisan migrate
php artisan db:seed --class=RolesSeeder

# Crear usuario admin por CLI (opcional, crea/actualiza y asigna rol)
php artisan user:create --email=admin@example.com --name="Admin" --password=secret --role=Admin

# Servidores
php artisan serve
npm run dev
```

## Acceso rápido

- Web: http://localhost:8000
- Login: `/login` (usa el usuario creado por `user:create`)
- Panel usuarios Admin (Inertia): `/admin/users-ui` (requiere rol `Admin`)

## Características destacadas

- Roles y permisos con Spatie (middlewares de rol, `HasRoles` en `User`).
- Notificaciones por base de datos; endpoints de ejemplo y test.
- Settings con servicio + caché + facade (`Settings::get('key')`).
- Admin Users:
  - API JSON con búsqueda, orden, paginación y filtros (rol, fecha)
  - UI con DataTable (skeleton/empty), View/Edit/Delete y toasts
- Comando Artisan `user:create` para crear/promocionar usuarios con rol.

## Scripts útiles

```bash
# Ejecutar pruebas (Pest)
php artisan test

# Construir assets
npm run build

# Formato/Lint Backend (PHP - Laravel Pint)
composer run format:php       # aplica formato
composer run lint:php         # verifica formato (CI)

# Formato/Lint Frontend
npm run format                # Prettier write
npm run format:check          # Prettier check (CI)
npm run lint                  # ESLint fix
npm run lint:check            # ESLint check (CI)

# Tipos TypeScript
npm run types
```

## Stack

- Laravel 12, Pest
- Spatie Laravel Permission
- Inertia.js + React + TypeScript
- Tailwind CSS 4

## Contribución

- Seguir PSR-12/PSR-4.
- Nombres en inglés; comentarios explicativos en español cuando aporten valor.
- Ver mejoras pendientes y roadmap en `docs/`.

### Estándares de código

- PHP: Laravel Pint preset `laravel` con reglas adicionales (`ordered_imports`, sin `phpdoc_align`). Configuración en `pint.json`.
- JS/TS/React: ESLint 9 + `eslint-config-prettier`, plugins React/Hooks. Prettier con plugins `organize-imports` y `tailwindcss`.
- CI (`.github/workflows/lint.yml`):
  - `vendor/bin/pint --test`
  - `npm run format:check`
  - `npm run lint:check`
