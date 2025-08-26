<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'tenant_id' => $this->tenant_id,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Campos extra para control de UI (no afectan tests que validan estructura mínima)
            'roles' => $this->whenLoaded('roles', fn () => $this->getRoleNames()),
            'is_root' => $this->getRoleNames()->map(fn ($r) => strtolower($r))->contains('root'),
        ];
    }
}
