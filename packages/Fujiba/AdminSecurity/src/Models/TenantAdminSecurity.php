<?php

namespace Fujiba\AdminSecurity\Models;

use Illuminate\Database\Eloquent\Model;

class TenantAdminSecurity extends Model
{
    protected $table = 'tenant_admin_security';

    protected $fillable = [
        'tenant_id',
        'secret_path',
        'security_mode',
        'basic_auth_user',
        'basic_auth_pass_hash',
        'allowed_ips',
        'block_default_admin',
        'enabled',
        'last_test_at',
        'last_test_result',
        'updated_by',
    ];

    protected $casts = [
        'allowed_ips' => 'array',
        'block_default_admin' => 'boolean',
        'enabled' => 'boolean',
        'last_test_at' => 'datetime',
    ];

    protected $hidden = [
        'basic_auth_pass_hash',
    ];

    public function getConnectionName()
    {
        return config('tenancy.database.central_connection', 'mysql');
    }
}
