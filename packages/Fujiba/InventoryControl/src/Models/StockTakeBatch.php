<?php

namespace Fujiba\InventoryControl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Product\Models\ProductProxy;

class StockTakeBatch extends Model
{
    protected $fillable = [
        'session_id',
        'item_id',
        'inventory_batch_id',
        'product_id',
        'batch_number',
        'expiry_date',
        'baseline_qty',
        'counted_qty',
        'difference',
        'scanned_by',
        'scanned_at',
        'applied',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'scanned_at'  => 'datetime',
        'applied'     => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(StockTakeSession::class, 'session_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(StockTakeItem::class, 'item_id');
    }

    public function inventoryBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }
}
