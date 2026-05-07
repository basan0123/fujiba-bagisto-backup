<?php

namespace Fujiba\OrdersAdmin\Carriers;

use Webkul\Checkout\Models\CartShippingRate;
use Webkul\Shipping\Carriers\AbstractShipping;

class Pickup extends AbstractShipping
{
    /**
     * Shipping method carrier code.
     */
    protected $code = 'pickup';

    /**
     * Shipping method code.
     */
    protected $method = 'pickup_pickup';

    public function calculate()
    {
        if (! $this->isAvailable()) {
            return false;
        }

        return $this->getRate();
    }

    public function getRate(): CartShippingRate
    {
        $rate = new CartShippingRate;
        $rate->carrier            = $this->getCode();
        $rate->carrier_title      = $this->getConfigData('title');
        $rate->method             = $this->getMethod();
        $rate->method_title       = $this->getConfigData('title');
        $rate->method_description = $this->getConfigData('description');
        $rate->price              = 0;
        $rate->base_price         = 0;

        return $rate;
    }
}
