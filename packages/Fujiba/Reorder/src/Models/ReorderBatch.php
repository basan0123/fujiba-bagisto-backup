<?php

namespace Fujiba\Reorder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Core\Models\ChannelProxy;

class ReorderBatch extends Model
{
    public const STATUS_DRAFT     = 'draft';     // user is editing, not yet confirmed for receive
    public const STATUS_PENDING   = 'pending';   // confirmed, awaiting receive
    public const STATUS_RECEIVING = 'receiving'; // user is on the receive screen, draft of qtys saved
    public const STATUS_RECEIVED  = 'received';  // closed, stock applied

    protected $table = 'reorder_batches';

    protected $fillable = [
        'channel_id',
        'status',
        'note',
        'created_by',
        'received_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ReorderItem::class, 'reorder_batch_id');
    }

    public function channel()
    {
        return $this->belongsTo(ChannelProxy::modelClass(), 'channel_id');
    }
}
