<?php

namespace Webkul\Pos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Pos\Contracts\WasteEntry as WasteEntryContract;

class WasteEntry extends Model implements WasteEntryContract
{
    protected $table = 'pos_waste_entries';

    protected $fillable = [
        'outlet_id',
        'user_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_cost',
        'total_cost',
        'reason',
        'notes',
        'verified_by',
        'verified_at',
        'status',
        'wasted_at',
    ];

    protected $casts = [
        'wasted_at'   => 'date',
        'verified_at' => 'datetime',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(OutletProxy::modelClass(), 'outlet_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass(), 'user_id');
    }
}
