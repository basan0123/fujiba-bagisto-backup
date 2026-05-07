<?php

namespace Webkul\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingZoneRate extends Model
{
    protected $fillable = ['zone_id', 'qty_from', 'qty_to', 'shipping_price', 'is_active'];

    protected $casts = [
        'shipping_price' => 'float',
        'is_active'      => 'boolean',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'zone_id');
    }
}
