<?php

namespace Webkul\Komoju\Payment;

use Illuminate\Support\Facades\Log;
use Webkul\Komoju\Services\PaymentManagerService;
use Webkul\Payment\Payment\Payment;

class Komoju extends Payment
{
    protected $code = 'komoju';

    public function __construct(protected ?PaymentManagerService $manager = null)
    {
        $this->manager ??= app(PaymentManagerService::class);
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getTitle()
    {
        return $this->getConfigData('title') ?: trans('komoju::app.title');
    }

    public function getDescription()
    {
        return $this->getConfigData('description') ?: trans('komoju::app.description');
    }

    public function isAvailable()
    {
        return $this->getConfigData('active') && $this->getConfigData('secret_key');
    }

    public function getRedirectUrl()
    {
        $cart = $this->getCart();
        $channel = core()->getCurrentChannelCode();

        if (! $cart) {
            return route('shop.checkout.cart.index');
        }

        $paymentTypes = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) $this->getConfigData('payment_types'))
        )));

        $response = $this->manager->request('POST', '/api/v1/sessions', [
            'amount' => (int) round($cart->grand_total),
            'currency' => $this->manager->currency($channel),
            'return_url' => route('komoju.callback', ['cart' => $cart->id]),
            'cancel_url' => route('komoju.cancel'),
            'payment_types' => $paymentTypes,
            'metadata' => [
                'cart_id' => (string) $cart->id,
                'channel' => $channel,
            ],
        ], $channel);

        $url = $response['session_url'] ?? ($response['payment_url'] ?? null);

        if ($url) {
            session(['komoju_session_id_'.$cart->id => $response['id'] ?? null]);

            return $url;
        }

        Log::error('komoju.redirect.failed', ['response' => $response, 'cart' => $cart->id]);

        return route('shop.checkout.cart.index');
    }
}
