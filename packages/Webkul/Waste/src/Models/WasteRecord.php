<?php

namespace Webkul\Waste\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteRecord extends Model
{
    protected $table = 'waste_records';

    protected $fillable = [
        'product_id',
        'product_name',
        'product_sku',
        'channel_id',
        'quantity',
        'amount',
        'reason',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'float',
        'amount'   => 'float',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function channel(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Core\Models\Channel::class, 'channel_id');
    }

    // product is in product_flat; a simple accessor is enough
    public function getProductAttribute()
    {
        if (! $this->product_id) {
            return null;
        }

        return \DB::table('product_flat')
            ->where('product_id', $this->product_id)
            ->where('locale', app()->getLocale())
            ->first(['product_id', 'name', 'sku', 'price']);
    }
}
