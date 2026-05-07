<?php

namespace Webkul\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FreeShippingThreshold extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'channel_id', 'threshold_amount', 'label',
        'apply_to_cool', 'apply_to_frozen',
        'excluded_categories', 'prefecture_codes', 'base_shipping_fee',
        'priority', 'is_active',
    ];

    protected $casts = [
        'threshold_amount'    => 'float',
        'base_shipping_fee'   => 'float',
        'apply_to_cool'       => 'boolean',
        'apply_to_frozen'     => 'boolean',
        'excluded_categories' => 'array',
        'prefecture_codes'    => 'array',
        'is_active'           => 'boolean',
    ];

    public function scopeActive($q)               { return $q->where('is_active', true); }
    public function scopeByPriority($q)           { return $q->orderBy('priority'); }
    public function scopeForChannel($q, $id)      { return $q->where('channel_id', $id); }
    public function scopeGlobalOrChannel($q, $id) { return $q->where(fn($q) => $q->whereNull('channel_id')->orWhere('channel_id', $id)); }
}
