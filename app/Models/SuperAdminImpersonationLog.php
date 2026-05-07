<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuperAdminImpersonationLog extends Model
{
    protected $table = 'super_admin_impersonation_log';

    public $timestamps = false;

    protected $fillable = [
        'super_admin_id',
        'tenant_id',
        'target_admin_email',
        'reason',
        'ip',
        'user_agent',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];
}
