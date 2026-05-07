<?php

namespace Fujiba\LiveChat\Models;

use Illuminate\Database\Eloquent\Model;

class AutoFlowDispatchLog extends Model
{
    protected $connection = 'mysql';
    protected $table = 'auto_flow_dispatch_log';

    protected $fillable = [
        'tenant_id',
        'channel_id',
        'fb_bot_id',
        'subscriber_id',
        'postback_id',
        'fb_thread_id',
        'candidate_seen_at',
        'dispatched_at',
        'status',
        'steps_total',
        'steps_sent',
        'error_message',
    ];

    protected $casts = [
        'candidate_seen_at' => 'datetime',
        'dispatched_at'     => 'datetime',
    ];
}
