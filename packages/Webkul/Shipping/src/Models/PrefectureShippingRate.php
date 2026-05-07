<?php

namespace Webkul\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrefectureShippingRate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'channel_id', 'prefecture_code', 'prefecture_name_ja',
        'prefecture_name_en', 'qty_from', 'qty_to',
        'shipping_price', 'priority', 'is_active',
    ];

    protected $casts = [
        'qty_from'       => 'integer',
        'qty_to'         => 'integer',
        'shipping_price' => 'float',
        'is_active'      => 'boolean',
    ];

    public function scopeActive($q)               { return $q->where('is_active', true); }
    public function scopeByPriority($q)           { return $q->orderBy('priority'); }
    public function scopeForChannel($q, $id)      { return $q->where('channel_id', $id); }
    public function scopeGlobalOrChannel($q, $id) { return $q->where(fn($q) => $q->whereNull('channel_id')->orWhere('channel_id', $id)); }
    public function scopeForPrefecture($q, $code) { return $q->where('prefecture_code', $code); }
}
