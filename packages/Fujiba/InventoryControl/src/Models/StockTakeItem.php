<?php

namespace Fujiba\InventoryControl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Product\Models\ProductProxy;

class StockTakeItem extends Model
{
    public function batches(): HasMany
    {
        return $this->hasMany(StockTakeBatch::class, 'item_id');
    }

    protected $fillable = [
        'session_id',
        'product_id',
        'parent_product_id',
        'sku',
        'name',
        'variant_label',
        'matched_by',
        'scanned_by',
        'scanned_at',
        'system_qty',
        'baseline_qty',
        'counted_qty',
        'difference',
        'notes',
        'applied',
    ];

    protected $casts = [
        'applied'    => 'boolean',
        'scanned_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(StockTakeSession::class, 'session_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }
}
