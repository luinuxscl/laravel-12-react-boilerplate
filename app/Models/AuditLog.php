<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'entity_type',
        'entity_id',
        'action',
        'changes',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'changes' => 'array',
    ];
}
