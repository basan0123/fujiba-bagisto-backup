<?php

namespace Webkul\Payment;

use Illuminate\Support\Facades\Config;

class Payment
{
    /**
     * Returns all supported payment methods
     *
     * @param  string  $context  'admin' (default) includes admin_only methods; 'shop' filters them out.
     * @return array
     */
    public function getSupportedPaymentMethods($context = 'admin')
    {
        return [
            'payment_methods' => $this->getPaymentMethods($context),
        ];
    }

    /**
     * Returns all supported payment methods
     *
     * @param  string  $context  'admin' (default) includes admin_only methods; 'shop' filters them out.
     * @return array
     */
    public function getPaymentMethods($context = 'admin')
    {
        $paymentMethods = [];

        foreach (Config::get('payment_methods') as $paymentMethodConfig) {
            // admin_only methods (e.g. cash) must never appear on the storefront.
            if ($context === 'shop' && ! empty($paymentMethodConfig['admin_only'])) {
                continue;
            }

            $paymentMethod = app($paymentMethodConfig['class']);

            if ($paymentMethod->isAvailable()) {
                $paymentMethods[] = [
                    'method' => $paymentMethod->getCode(),
                    'method_title' => $paymentMethod->getTitle(),
                    'description' => $paymentMethod->getDescription(),
                    'sort' => $paymentMethod->getSortOrder(),
                    'image' => $paymentMethod->getImage(),
                ];
            }
        }

        usort($paymentMethods, function ($a, $b) {
            if ($a['sort'] == $b['sort']) {
                return 0;
            }

            return ($a['sort'] < $b['sort']) ? -1 : 1;
        });

        return $paymentMethods;
    }

    /**
     * Returns payment redirect url if have any
     *
     * @param  \Webkul\Checkout\Contracts\Cart  $cart
     * @return string
     */
    public function getRedirectUrl($cart)
    {
        $payment = app(Config::get('payment_methods.'.$cart->payment->method.'.class'));

        return $payment->getRedirectUrl();
    }

    /**
     * Returns payment method additional information
     *
     * @param  string  $code
     * @return array
     */
    public static function getAdditionalDetails($code)
    {
        $class = Config::get('payment_methods.'.$code.'.class');

        if (! $class) {
            return [];
        }

        return app($class)->getAdditionalDetails();
    }
}
