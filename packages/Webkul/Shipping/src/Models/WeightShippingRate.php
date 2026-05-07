<?php

namespace Webkul\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WeightShippingRate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'channel_id', 'prefecture_code', 'weight_from',
        'weight_to', 'shipping_price', 'label', 'priority', 'is_active',
    ];

    protected $casts = [
        'weight_from'    => 'float',
        'weight_to'      => 'float',
        'shipping_price' => 'float',
        'is_active'      => 'boolean',
    ];

    public function scopeActive($q)               { return $q->where('is_active', true); }
    public function scopeByPriority($q)           { return $q->orderBy('priority'); }
    public function scopeForChannel($q, $id)      { return $q->where('channel_id', $id); }
    public function scopeGlobalOrChannel($q, $id) { return $q->where(fn($q) => $q->whereNull('channel_id')->orWhere('channel_id', $id)); }
}
