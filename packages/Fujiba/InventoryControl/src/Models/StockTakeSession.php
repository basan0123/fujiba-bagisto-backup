<?php

namespace Fujiba\InventoryControl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Inventory\Models\InventorySourceProxy;

class StockTakeSession extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_CANCELLED = 'cancelled';

    public const BASELINE_NONE = 'none';
    public const BASELINE_PENDING = 'pending';
    public const BASELINE_READY = 'ready';

    protected $fillable = [
        'name',
        'inventory_source_id',
        'channel_id',
        'status',
        'advanced_mode',
        'baseline_status',
        'baseline_count',
        'created_by',
        'applied_by',
        'applied_at',
        'notes',
    ];

    protected $casts = [
        'applied_at'    => 'datetime',
        'advanced_mode' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(StockTakeItem::class, 'session_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(StockTakeBatch::class, 'session_id');
    }

    public function inventorySource(): BelongsTo
    {
        return $this->belongsTo(InventorySourceProxy::modelClass());
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(ChannelProxy::modelClass());
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}
