<?php

namespace Fujiba\SagawaDelivery\Carriers;

use Fujiba\SagawaDelivery\Models\Credential;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\CartShippingRate;
use Webkul\Shipping\Carriers\AbstractShipping;

class Sagawa extends AbstractShipping
{
    protected $code = 'sagawa';

    protected $method = 'sagawa_sagawa';

    public function calculate()
    {
        if (! $this->isAvailable()) {
            return false;
        }

        // Honour per-channel Sagawa rate toggle: if the admin has NOT turned on
        // "Use Sagawa Rate" for this channel, yield so rule-based shipping
        // handlers can provide the price instead.
        if (! $this->isSagawaRateEnabledForChannel()) {
            return false;
        }

        return $this->getRate();
    }

    public function getRate(): CartShippingRate
    {
        $cart = Cart::getCart();

        $rate = (float) ($this->getConfigData('default_rate') ?? 0);

        $cartShippingRate = new CartShippingRate;
        $cartShippingRate->carrier             = $this->getCode();
        $cartShippingRate->carrier_title       = $this->getConfigData('title');
        $cartShippingRate->method              = $this->getMethod();
        $cartShippingRate->method_title        = $this->getConfigData('title');
        $cartShippingRate->method_description  = $this->getConfigData('description');
        $cartShippingRate->price               = core()->convertPrice($rate);
        $cartShippingRate->base_price          = $rate;

        return $cartShippingRate;
    }

    protected function isSagawaRateEnabledForChannel(): bool
    {
        $tenantId  = function_exists('tenant') && tenant() ? (string) tenant()->id : null;
        $channelId = (int) (core()->getCurrentChannel()->id ?? 0);

        if (! $tenantId || ! $channelId) {
            return false;
        }

        $cred = Credential::query()
            ->where('tenant_id', $tenantId)
            ->where('channel_id', $channelId)
            ->first();

        return (bool) ($cred?->use_sagawa_rate);
    }
}
