<?php

namespace Fujiba\PwaFrontend\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\Checkout\Facades\Cart;
use Webkul\Payment\Facades\Payment;

/**
 * GET /api/v1/pwa-frontend/payment-methods
 *
 * Returns the list of payment methods supported for the active cart, filtered
 * through the per-channel admin allow-list (pwa.payments.*). Used by the chat
 * checkout when /api/v1/customer/checkout/save-address didn't include methods
 * (which happens for guest carts and a few edge paths).
 */
class PaymentController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            $supported = Payment::getSupportedPaymentMethods();
        } catch (\Throwable $e) {
            $supported = ['payment_methods' => []];
        }

        $methods = $supported['payment_methods'] ?? [];

        // Filter via admin allow-list (Fujiba PwaFrontend pwa.payments.*).
        $channelCode = core()->getCurrentChannel()?->code;
        $cfg = fn (string $key) => core()->getConfigData($key, $channelCode);
        $allow = [
            'cashondelivery' => $this->bool($cfg('pwa.payments.cod_enabled'), true),
            'moneytransfer'  => $this->bool($cfg('pwa.payments.transfer_enabled'), true),
            'card'           => $this->bool($cfg('pwa.payments.card_enabled')),
            'paypay'         => $this->bool($cfg('pwa.payments.paypay_enabled')),
            'komoju'         => $this->bool($cfg('pwa.payments.komoju_enabled')),
        ];
        $methods = array_values(array_filter($methods, function ($m) use ($allow) {
            $code = $m['method'] ?? null;
            // If the method isn't listed in our allow-map (e.g. paypal_smart_button),
            // fall through to Bagisto's own enabled flag — Payment::getSupportedPaymentMethods
            // already filtered by Bagisto's enabled config.
            return $code === null
                || ! array_key_exists($code, $allow)
                || $allow[$code];
        }));

        // Apply COD min-qty rule (mirror of OnepageController logic).
        try {
            $cart = Cart::getCart();
            if ($cart) {
                $totalQty  = (int) ($cart->items_qty ?? $cart->items->sum('quantity'));
                $codMinQty = (int) core()->getConfigData('sales.payment_methods.cashondelivery.cod_min_qty') ?: 0;
                if ($codMinQty > 0 && $totalQty < $codMinQty) {
                    $methods = array_values(array_filter($methods, fn ($m) => ($m['method'] ?? null) !== 'cashondelivery'));
                }
            }
        } catch (\Throwable) {}

        return response()->json([
            'data' => [
                'methods' => $methods,
            ],
        ]);
    }

    private function bool(mixed $value, bool $default = false): bool
    {
        if ($value === null || $value === '') return $default;
        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }
}
