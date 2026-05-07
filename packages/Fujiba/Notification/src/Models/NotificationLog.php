<?php

namespace Fujiba\Notification\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT    = 'sent';
    public const STATUS_FAILED  = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $table = 'notification_log';

    protected $fillable = [
        'event',
        'notification_channel',
        'subject_type',
        'subject_id',
        'customer_id',
        'recipient',
        'status',
        'error',
        'payload',
        'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
    ];

    public function markSent(): void
    {
        $this->update([
            'status'  => self::STATUS_SENT,
            'sent_at' => now(),
            'error'   => null,
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error'  => mb_substr($error, 0, 4000),
        ]);
    }
}
