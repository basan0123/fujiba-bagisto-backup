<?php

namespace Webkul\Pos\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryAdjustment extends Model
{
    protected $table = 'pos_inventory_adjustments';

    protected $fillable = [
        'product_id',
        'session_id',
        'before_qty',
        'after_qty',
        'reason',
        'adjusted_by',
    ];
}
