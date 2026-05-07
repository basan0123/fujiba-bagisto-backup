<?php

namespace Webkul\Shipping\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingRuleLog extends Model
{
    protected $fillable = [
        'channel_id', 'order_id', 'cart_id',
        'rules_applied', 'shipping_total', 'cool_fee_total',
        'free_shipping_applied', 'bulk_bonus_items', 'calculated_at',
    ];

    protected $casts = [
        'rules_applied'         => 'array',
        'bulk_bonus_items'      => 'array',
        'shipping_total'        => 'float',
        'cool_fee_total'        => 'float',
        'free_shipping_applied' => 'boolean',
        'calculated_at'         => 'datetime',
    ];
}
