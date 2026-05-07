<?php

namespace Webkul\Shipping\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class ShippingCalculatorController extends Controller
{
    public function calculate(Request $request, int $cartId)
    {
        return response()->json([
            'base_shipping'         => 0,
            'cool_fee'              => 0,
            'frozen_fee'            => 0,
            'total'                 => 0,
            'free_shipping_applied' => false,
            'bonus_items'           => [],
            'breakdown'             => [],
        ]);
    }
}
