<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\AuditLog
 */
class AuditLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'tenant_id' => $this->tenant_id,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'action' => $this->action,
            'changes' => $this->changes,
            'ip' => $this->ip,
            'user_agent' => $this->user_agent,
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
