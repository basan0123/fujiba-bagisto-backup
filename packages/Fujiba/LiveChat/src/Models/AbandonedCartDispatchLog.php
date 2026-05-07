<?php

namespace Fujiba\LiveChat\Models;

use Illuminate\Database\Eloquent\Model;

class AbandonedCartDispatchLog extends Model
{
    protected $connection = 'mysql';
    protected $table = 'abandoned_cart_dispatch_log';

    protected $fillable = [
        'tenant_id',
        'channel_id',
        'fb_bot_id',
        'cart_id',
        'customer_id',
        'subscriber_id',
        'step',
        'candidate_seen_at',
        'dispatched_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'candidate_seen_at' => 'datetime',
        'dispatched_at'     => 'datetime',
    ];
}
