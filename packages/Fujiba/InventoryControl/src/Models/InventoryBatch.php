<?php

namespace Fujiba\InventoryControl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Inventory\Models\InventorySourceProxy;
use Webkul\Product\Models\ProductProxy;

class InventoryBatch extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DEPLETED = 'depleted';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REMOVED = 'removed';

    protected $fillable = [
        'product_id',
        'channel_id',
        'inventory_source_id',
        'batch_number',
        'quantity_received',
        'quantity_remaining',
        'cost_price',
        'manufacture_date',
        'expiry_date',
        'status',
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date'      => 'date',
        'cost_price'       => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(ChannelProxy::modelClass());
    }

    public function inventorySource(): BelongsTo
    {
        return $this->belongsTo(InventorySourceProxy::modelClass());
    }
}
