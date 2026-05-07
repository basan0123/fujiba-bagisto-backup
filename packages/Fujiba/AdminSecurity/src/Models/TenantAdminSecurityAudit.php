<?php

namespace Fujiba\AdminSecurity\Models;

use Illuminate\Database\Eloquent\Model;

class TenantAdminSecurityAudit extends Model
{
    protected $table = 'tenant_admin_security_audit';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'actor_email',
        'actor_ip',
        'action',
        'before_state',
        'after_state',
        'note',
        'created_at',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state' => 'array',
        'created_at' => 'datetime',
    ];

    public function getConnectionName()
    {
        return config('tenancy.database.central_connection', 'mysql');
    }
}
