<?php

namespace Webkul\PayPay\Payment;

use Illuminate\Support\Facades\Log;
use Webkul\Payment\Payment\Payment;
use Webkul\PayPay\Services\PaymentManagerService;

class PayPay extends Payment
{
    protected $code = 'paypay';

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
        return $this->getConfigData('title') ?: trans('paypay::app.title');
    }

    public function getDescription()
    {
        return $this->getConfigData('description') ?: trans('paypay::app.description');
    }

    public function isAvailable()
    {
        return $this->getConfigData('active')
            && $this->getConfigData('api_key')
            && $this->getConfigData('api_secret')
            && $this->getConfigData('merchant_id');
    }

    public function getRedirectUrl()
    {
        $cart = $this->getCart();
        $channel = core()->getCurrentChannelCode();

        if (! $cart) {
            return route('shop.checkout.cart.index');
        }

        $merchantPaymentId = 'bagisto-'.$cart->id.'-'.bin2hex(random_bytes(6));

        $response = $this->manager->request('POST', '/v2/codes', [
            'merchantPaymentId' => $merchantPaymentId,
            'amount' => [
                'amount' => (int) round($cart->grand_total),
                'currency' => $this->manager->currency($channel),
            ],
            'codeType' => 'ORDER_QR',
            'orderDescription' => 'Bagisto Order #'.$cart->id,
            'isAuthorization' => false,
            'redirectUrl' => route('paypay.callback', ['cart' => $cart->id]),
            'redirectType' => 'WEB_LINK',
            'userAgent' => request()->userAgent(),
        ], $channel);

        if (! empty($response['data']['url'])) {
            session(['paypay_mpid_'.$cart->id => $merchantPaymentId]);

            return $response['data']['url'];
        }

        Log::error('paypay.redirect.failed', ['response' => $response, 'cart' => $cart->id]);

        return route('shop.checkout.cart.index');
    }
}
