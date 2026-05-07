<?php

namespace Fujiba\Reorder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Product\Models\ProductProxy;

/**
 * @deprecated 2026-04-27 — superseded by ReorderBatch + ReorderItem.
 * Kept to preserve historical reorder_todos rows. Scheduled for removal
 * after 2026-05-11 once the kimono tenant's 2 surviving rows are migrated
 * or confirmed disposable.
 *
 * Do NOT use for new code. New flow:
 *   - Save batch  -> POST /admin/inventory/reorder/batches  -> ReorderBatch
 *   - Receive     -> POST /admin/inventory/reorder/batches/{id}/confirm-receive
 */
class ReorderTodo extends Model
{
    public const STATUS_DRAFT   = 'draft';
    public const STATUS_ORDERED = 'ordered';
    public const STATUS_ARRIVED = 'arrived';
    public const STATUS_CLOSED  = 'closed';

    protected $table = 'reorder_todos';

    protected $fillable = [
        'product_id',
        'channel_id',
        'stock',
        'sale_count',
        'suggested_qty',
        'wish_qty',
        'arrived_qty',
        'cost',
        'status',
        'created_by',
    ];

    protected $casts = [
        'stock'         => 'integer',
        'sale_count'    => 'integer',
        'suggested_qty' => 'integer',
        'wish_qty'      => 'integer',
        'arrived_qty'   => 'integer',
        'cost'          => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'product_id');
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(ChannelProxy::modelClass(), 'channel_id');
    }

    public function subtotal(): float
    {
        return (float) $this->cost * (int) $this->wish_qty;
    }
}
