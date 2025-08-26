<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Support\TenantContext;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (config('tenancy.enabled', true)) {
                $tenantId = app(TenantContext::class)->id();
                if ($tenantId) {
                    $builder->where($builder->getModel()->getTable().'.tenant_id', $tenantId);
                }
            }
        });

        static::creating(function (self $model) {
            if (config('tenancy.enabled', true) && is_null($model->tenant_id)) {
                $tenantId = app(TenantContext::class)->id();
                if ($tenantId) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }
}
