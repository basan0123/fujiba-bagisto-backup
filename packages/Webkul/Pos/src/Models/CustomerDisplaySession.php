<?php

namespace Webkul\Pos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDisplaySession extends Model
{
    protected $table = 'pos_customer_display_sessions';

    protected $guarded = ['id'];

    protected $casts = [
        'last_payload'    => 'array',
        'last_payload_at' => 'datetime',
        'last_seen_at'    => 'datetime',
        'expires_at'      => 'datetime',
        'revoked_at'      => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass(), 'agent_id');
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(OutletProxy::modelClass(), 'outlet_id');
    }

    public function isLive(): bool
    {
        return $this->revoked_at === null && $this->expires_at->isFuture();
    }
}
