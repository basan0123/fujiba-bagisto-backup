<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuperAdminAuditLog extends Model
{
    protected $table = 'super_admin_audit_log';

    public $timestamps = false;

    protected $fillable = [
        'super_admin_id',
        'actor_email',
        'action',
        'target_tenant_id',
        'before',
        'after',
        'ip',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'before'     => 'array',
        'after'      => 'array',
        'created_at' => 'datetime',
    ];

    // Append-only: block deletes at the model level. The DB layer should not
    // have a DELETE policy granted to the app user either.
    protected static function booted(): void
    {
        static::deleting(function () {
            throw new \RuntimeException('Audit log is append-only; deletes are forbidden.');
        });

        static::updating(function () {
            throw new \RuntimeException('Audit log is append-only; updates are forbidden.');
        });
    }
}
