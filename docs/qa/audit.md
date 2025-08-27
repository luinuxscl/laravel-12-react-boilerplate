# Auditoría de acciones admin

Este documento describe el esquema y uso de la tabla `audit_logs` para trazabilidad de operaciones administrativas.

## Esquema
- Tabla: `audit_logs`
- Campos principales:
  - `user_id` (nullable): usuario autenticado que ejecutó la acción
  - `tenant_id` (nullable): tenant asociado cuando aplica
  - `entity_type`: clase o alias del recurso
  - `entity_id`: identificador del recurso (string)
  - `action`: `create` | `update` | `delete`
  - `changes` (json): diff o snapshot
  - `ip`, `user_agent`
  - `timestamps`
- Índices: `user_id`, `tenant_id`, `created_at`, `entity_type, entity_id`

## Fuentes de eventos
Actualmente se registran en:
- `UsersController@update`, `UsersController@destroy`
- `RolesController@store`, `@update`, `@destroy`
- `Admin\\SettingsController@update`, `@destroy`

## Ejemplos de consulta
Listar últimas 50 acciones:
```sql
select id, created_at, user_id, action, entity_type, entity_id
from audit_logs
order by id desc
limit 50;
```

Buscar por usuario:
```sql
select * from audit_logs where user_id = ? order by id desc;
```

Buscar por recurso:
```sql
select * from audit_logs
where entity_type = 'App\\Models\\User' and entity_id = '123';
```

## Buenas prácticas
- Usar `changes.before/after` para updates con campos acotados.
- Para deletes, se guarda `snapshot` con campos principales.
- Evitar almacenar datos sensibles en `changes`.
