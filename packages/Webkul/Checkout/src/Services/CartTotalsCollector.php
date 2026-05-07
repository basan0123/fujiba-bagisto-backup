<?php

namespace Webkul\Checkout\Services;

use Webkul\Sales\Models\Order;

/**
 * Extended Cart Totals Collector
 * 
 * Extends Bagisto's default totals collection with checkout rules.
 * Ensures shipping and COD fees are calculated consistently.
 * 
 * Hook into: Webkul\Checkout\Facades\Cart::getTotals()
 * 
 * Usage in Cart Model or totals calculation:
 *   $rulesService = new CheckoutRulesService();
 *   $totals = (new CartTotalsCollector())->collect($cart, $rulesService);
 */
class CartTotalsCollector
{
    /**
     * Collect cart totals with checkout rules applied
     */
    public function collect($cart, CheckoutRulesService $rulesService = null)
    {
        if (!$rulesService) {
            $rulesService = new CheckoutRulesService();
        }

        // Get cart subtotal (sum of all items)
        $subtotal = $this->getCartSubtotal($cart);

        // Get shipping cost based on cart total
        $shippingPrice = $rulesService->getShippingPrice($subtotal);

        // Get payment method (if selected)
        $paymentMethod = $cart->payment_method ?? null;

        // Get COD fee based on cart total and payment method
        $codFee = $rulesService->getCODFee($subtotal, $paymentMethod);

        // Calculate tax (if applicable)
        $shippingTax = $this->calculateTax($shippingPrice, 'shipping', $rulesService);
        $codTax = $this->calculateTax($codFee, 'cod', $rulesService);

        // Calculate grand total
        $grandTotal = $subtotal 
            + $shippingPrice 
            + $shippingTax 
            + $codFee 
            + $codTax;

        return [
            'subtotal' => (float) $subtotal,
            'subtotal_formatted' => '¥' . number_format($subtotal, 0),
            
            'shipping_price' => (float) $shippingPrice,
            'shipping_price_formatted' => '¥' . number_format($shippingPrice, 0),
            'shipping_tax' => (float) $shippingTax,
            
            'cod_fee' => (float) $codFee,
            'cod_fee_formatted' => '¥' . number_format($codFee, 0),
            'cod_tax' => (float) $codTax,
            
            'total_tax' => (float) ($shippingTax + $codTax),
            'total_tax_formatted' => '¥' . number_format($shippingTax + $codTax, 0),
            
            'grand_total' => (float) $grandTotal,
            'grand_total_formatted' => '¥' . number_format($grandTotal, 0),
            
            'payment_method' => $paymentMethod,
            'rules_applied' => [
                'shipping_method' => $rulesService->getAutoSelectShippingMethod($subtotal)['code'] ?? null,
                'cod_enabled' => $codFee > 0,
            ],
        ];
    }

    /**
     * Get cart subtotal
     */
    protected function getCartSubtotal($cart)
    {
        $total = 0;

        if (method_exists($cart, 'items')) {
            foreach ($cart->items as $item) {
                $total += ($item->price * $item->quantity);
            }
        } else if (is_array($cart)) {
            $total = $cart['subtotal'] ?? 0;
        }

        return (float) $total;
    }

    /**
     * Calculate tax if applicable
     */
    protected function calculateTax($amount, $type, CheckoutRulesService $rulesService)
    {
        $rules = $rulesService->rules ?? [];
        $totalsConfig = $rules['totals'] ?? [];

        // Check if tax should be applied
        if ($type === 'shipping' && !($totalsConfig['tax_on_shipping'] ?? false)) {
            return 0;
        }

        if ($type === 'cod' && !($totalsConfig['tax_on_cod'] ?? false)) {
            return 0;
        }

        // Tax rate from per-channel config (defaults to 10% — Japan consumption tax)
        $rate = (float) ($totalsConfig['tax_rate'] ?? 0.10);

        return round($amount * $rate, 0);
    }
}
