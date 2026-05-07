<?php

namespace Fujiba\Accounting\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Append-only audit log. Updates and deletes are forbidden at the model layer
 * to make accidental tampering loud.
 */
class AuditLog extends Model
{
    protected $table = 'fujiba_audit_log';

    public $timestamps = false;
    protected $guarded = ['id'];

    protected $casts = [
        'before_json' => 'array',
        'after_json'  => 'array',
        'created_at'  => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('fujiba_audit_log is append-only — updates are forbidden.');
        });

        static::deleting(function () {
            throw new \RuntimeException('fujiba_audit_log is append-only — deletes are forbidden.');
        });
    }
}
