<?php

namespace Fujiba\Dashboard\Models;

use Illuminate\Database\Eloquent\Model;

class OrderShippingCost extends Model
{
    protected $table = 'order_shipping_costs';

    protected $fillable = [
        'order_id', 'tracking_number', 'carrier_cost',
        'carrier_code', 'billing_month', 'source', 'notes',
    ];

    protected $casts = [
        'carrier_cost' => 'float',
    ];
}
