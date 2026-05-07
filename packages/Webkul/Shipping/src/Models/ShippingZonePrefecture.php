<?php

namespace Webkul\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingZonePrefecture extends Model
{
    protected $fillable = ['zone_id', 'prefecture_code', 'prefecture_name_ja'];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'zone_id');
    }
}
