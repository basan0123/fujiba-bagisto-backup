<?php

namespace Webkul\Pos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StocktakeItem extends Model
{
    protected $table = 'pos_stocktake_items';

    protected $fillable = [
        'session_id',
        'product_id',
        'sku',
        'system_qty',
        'counted_qty',
        'difference',
        'notes',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(StocktakeSession::class, 'session_id');
    }
}
