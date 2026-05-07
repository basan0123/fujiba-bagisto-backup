<?php

namespace Fujiba\Reorder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Product\Models\ProductProxy;

class ReorderItem extends Model
{
    protected $table = 'reorder_items';

    protected $fillable = [
        'reorder_batch_id',
        'product_id',
        'name_snapshot',
        'qty',
        'delivered_qty',
        'damage_qty',
        'loss_qty',
        'cut_off_qty',
        'to_stock_qty',
        'cost',
    ];

    protected $casts = [
        'qty'           => 'integer',
        'delivered_qty' => 'integer',
        'damage_qty'    => 'integer',
        'loss_qty'      => 'integer',
        'cut_off_qty'   => 'integer',
        'to_stock_qty'  => 'integer',
        'cost'          => 'decimal:4',
    ];

    /** Default fill-to-stock = delivered − damage − loss − cut_off, clamped to 0. */
    public function defaultToStockQty(): int
    {
        return max(0,
            (int) $this->delivered_qty
            - (int) $this->damage_qty
            - (int) $this->loss_qty
            - (int) $this->cut_off_qty
        );
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ReorderBatch::class, 'reorder_batch_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'product_id');
    }
}
