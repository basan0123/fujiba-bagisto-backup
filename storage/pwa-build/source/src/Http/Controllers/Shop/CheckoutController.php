<?php

namespace Webkul\PWA\Http\Controllers\Shop;

use Webkul\Checkout\Facades\Cart;
use Webkul\Shipping\Facades\Shipping;
use Webkul\Payment\Facades\Payment;
use Webkul\RestApi\Http\Resources\V1\Shop\Checkout\CartResource;
use Webkul\RestApi\Http\Resources\V1\Shop\Checkout\CartShippingRateResource;
use Webkul\PWA\Http\Controllers\Controller;

class CheckoutController extends Controller
{
    /**
     * Saves customer address and returns shipping options + payment methods.
     *
     * Order matters: we save the address FIRST so Bagisto's shipping code
     * can use it when collecting rates. The original Bagisto-PWA flow
     * collected rates before the address existed which meant
     * `Shipping::collectRates()` returned nothing for any address-aware
     * carrier (Sagawa, in our case).
     *
     * On error we return 422 with structured cart errors so the chat
     * checkout UI can show a helpful message instead of the opaque 400.
     *
     * NOTE: this file is the SOURCE of truth — pwa-build.sh rsyncs it
     * into vendor/bagisto/pwa/src on every build, so the vendor copy
     * gets overwritten. Edit this file, not the vendor file.
     */
    public function saveAddress()
    {
        $data = request()->all();

        // 1. Save the address first so Shipping::collectRates() can use it.
        try {
            Cart::saveAddresses($data);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage() ?: 'Could not save address.',
            ], 422);
        }

        if (Cart::hasError()) {
            $errors = Cart::getErrors();
            return response()->json([
                'message' => $errors['message'] ?? 'Cart has errors.',
                'errors'  => $errors,
            ], 422);
        }

        // 2. Now collect rates (address-aware).
        $shipping_methods = Shipping::collectRates();

        Cart::collectTotals();
        $cart = Cart::getCart();

        // Virtual / non-stockable cart → straight to payment.
        if (! $cart->haveStockableItems()) {
            return response()->json([
                'nextStep' => 'payment',
                'cart'     => new CartResource(Cart::getCart()),
                'methods'  => Payment::getSupportedPaymentMethods(),
            ]);
        }

        $rates = [];
        if ($shipping_methods && ! empty($shipping_methods['shippingMethods'])) {
            foreach ($shipping_methods['shippingMethods'] as $shippingMethod) {
                $rates[] = [
                    'carrier_title' => $shippingMethod['carrier_title'],
                    'rates'         => CartShippingRateResource::collection(collect($shippingMethod['rates'])),
                ];
            }
        }

        // No carriers ship to this address — let the client move on to
        // delivery slot / payment gracefully instead of hard-failing.
        if (empty($rates)) {
            return response()->json([
                'nextStep' => 'payment',
                'cart'     => new CartResource(Cart::getCart()),
                'methods'  => Payment::getSupportedPaymentMethods(),
                'message'  => 'No shipping carriers available for this address.',
            ]);
        }

        return response()->json([
            'data'    => [
                'rates' => $rates,
                'cart'  => new CartResource(Cart::getCart()),
            ],
            'nextStep' => 'shipping',
            'message'  => trans('rest-api::app.shop.checkout.billing-address-saved'),
        ]);
    }
}
