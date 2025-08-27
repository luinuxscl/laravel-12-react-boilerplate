<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Support\TenantContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * Registra una entrada de auditoría.
     *
     * @param  string  $action  create|update|delete
     * @param  string|Model  $entity  Clase/alias o instancia del recurso
     * @param  string|int|null  $entityId  Identificador del recurso (si $entity es string)
     * @param  array  $changes  Diff o payload de cambios
     */
    public function log(string $action, string|Model $entity, string|int|null $entityId = null, array $changes = []): AuditLog
    {
        $userId = $this->getUserId();
        $tenantId = $this->getTenantId();
        $ip = request()->ip();
        $userAgent = (string) request()->header('User-Agent');

        if ($entity instanceof Model) {
            $entityType = $entity::class;
            $entityId = $entity->getKey();
        } else {
            $entityType = $entity;
        }

        return AuditLog::create([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'entity_type' => $entityType,
            'entity_id' => (string) ($entityId ?? ''),
            'action' => $action,
            'changes' => $changes ?: null,
            'ip' => $ip,
            'user_agent' => $userAgent,
        ]);
    }

    protected function getUserId(): ?int
    {
        $user = auth()->user();
        return $user instanceof Authenticatable ? (int) $user->getAuthIdentifier() : null;
    }

    protected function getTenantId(): ?int
    {
        if (! config('tenancy.enabled', true)) {
            return null;
        }
        try {
            $tenantContext = app(TenantContext::class);
            return $tenantContext->id();
        } catch (\Throwable) {
            return null;
        }
    }
}
