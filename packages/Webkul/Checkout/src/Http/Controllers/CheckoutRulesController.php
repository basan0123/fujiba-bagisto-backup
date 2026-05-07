<?php

namespace Webkul\Checkout\Http\Controllers;

use Webkul\Checkout\Services\CheckoutRulesService;
use Webkul\Checkout\Services\CartTotalsCollector;
use Illuminate\Http\JsonResponse;

/**
 * Checkout Rules API Controller
 * 
 * Provides endpoints for checkout state, shipping methods, and COD fees.
 * Used by Vue checkout component for realtime calculations.
 * 
 * Routes to add in routes/api.php:
 *   Route::get('checkout/state', [CheckoutRulesController::class, 'getCheckoutState']);
 *   Route::get('cart/subtotal', [CheckoutRulesController::class, 'getCartSubtotal']);
 *   Route::post('checkout/place-order', [CheckoutRulesController::class, 'placeOrder']);
 */
class CheckoutRulesController extends Controller
{
    protected $rulesService;
    protected $totalsCollector;

    public function __construct()
    {
        $this->rulesService = new CheckoutRulesService();
        $this->totalsCollector = new CartTotalsCollector();
    }

    /**
     * Get checkout state with shipping methods and COD fees
     * 
     * GET /api/checkout/state?cart_total=8000&payment_method=cashondelivery
     */
    public function getCheckoutState(): JsonResponse
    {
        $cartTotal = (float) request('cart_total', 0);
        $paymentMethod = request('payment_method');

        // Get checkout state from rules service
        $state = $this->rulesService->getCheckoutState($cartTotal, $paymentMethod);

        return response()->json($state);
    }

    /**
     * Get current cart subtotal
     * 
     * GET /api/cart/subtotal
     */
    public function getCartSubtotal(): JsonResponse
    {
        $cart = auth()->user()->cart ?? null;

        if (!$cart) {
            return response()->json(['subtotal' => 0]);
        }

        $subtotal = $this->calculateCartSubtotal($cart);

        return response()->json([
            'subtotal' => (float) $subtotal,
            'subtotal_formatted' => '¥' . number_format($subtotal, 0),
        ]);
    }

    /**
     * Get shipping methods for cart total
     * 
     * GET /api/checkout/shipping-methods?cart_total=8000
     */
    public function getShippingMethods(): JsonResponse
    {
        $cartTotal = (float) request('cart_total', 0);
        $methods = $this->rulesService->getValidShippingMethods($cartTotal);

        return response()->json([
            'methods' => $methods,
            'auto_selected' => $this->rulesService->getAutoSelectShippingMethod($cartTotal),
        ]);
    }

    /**
     * Get COD fee for cart total and payment method
     * 
     * GET /api/checkout/cod-fee?cart_total=10000&payment_method=cashondelivery
     */
    public function getCODFee(): JsonResponse
    {
        $cartTotal = (float) request('cart_total', 0);
        $paymentMethod = request('payment_method');

        $fee = $this->rulesService->getCODFee($cartTotal, $paymentMethod);

        return response()->json([
            'fee' => (float) $fee,
            'fee_formatted' => '¥' . number_format($fee, 0),
            'enabled' => $fee > 0,
        ]);
    }

    /**
     * Calculate complete totals with rules applied
     * 
     * POST /api/checkout/calculate-totals
     */
    public function calculateTotals(): JsonResponse
    {
        $cart = auth()->user()->cart ?? null;
        $paymentMethod = request('payment_method');

        if (!$cart) {
            return response()->json(['error' => 'No cart found'], 404);
        }

        // Collect totals using rules
        $totals = $this->totalsCollector->collect($cart, $this->rulesService);

        return response()->json($totals);
    }

    /**
     * Place order with validated checkout data
     * 
     * POST /api/checkout/place-order
     * {
     *   "shipping_method": "free-shipping",
     *   "shipping_price": 0,
     *   "payment_method": "cashondelivery",
     *   "cod_fee": 440,
     *   "grand_total": 8440
     * }
     */
    public function placeOrder(): JsonResponse
    {
        try {
            $user = auth()->user();
            $cart = $user->cart ?? null;

            if (!$cart || $cart->items()->count() === 0) {
                return response()->json(['error' => 'Cart is empty'], 400);
            }

            // Validate request data
            $validated = request()->validate([
                'shipping_method' => 'required|string',
                'shipping_price' => 'required|numeric|min:0',
                'payment_method' => 'required|string',
                'cod_fee' => 'required|numeric|min:0',
                'grand_total' => 'required|numeric|min:0',
            ]);

            // Verify shipping method is valid
            $validMethods = $this->rulesService->getValidShippingMethods($this->calculateCartSubtotal($cart));
            $isValidMethod = collect($validMethods)->pluck('code')->contains($validated['shipping_method']);

            if (!$isValidMethod) {
                return response()->json(['error' => 'Invalid shipping method'], 400);
            }

            // Verify COD fee is correct
            $expectedCodFee = $this->rulesService->getCODFee(
                $this->calculateCartSubtotal($cart),
                $validated['payment_method']
            );

            if ((float) $validated['cod_fee'] !== (float) $expectedCodFee) {
                return response()->json(['error' => 'COD fee mismatch'], 400);
            }

            // Create order (integrate with your order creation logic)
            $order = $this->createOrder($cart, $validated);

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->increment_id,
                'message' => 'Order placed successfully',
            ]);

        } catch (\Throwable $e) {
            \Log::error('Order placement failed:', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to place order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate cart subtotal
     */
    protected function calculateCartSubtotal($cart)
    {
        $total = 0;

        if (method_exists($cart, 'items')) {
            foreach ($cart->items as $item) {
                $total += ($item->price * $item->quantity);
            }
        }

        return (float) $total;
    }

    /**
     * Create order from cart (stub - implement with your order logic)
     */
    protected function createOrder($cart, $data)
    {
        // This is a stub. Implement with your actual order creation logic.
        // Example:
        // $order = Order::create([
        //     'user_id' => auth()->id(),
        //     'subtotal' => $cart->subtotal,
        //     'shipping_method' => $data['shipping_method'],
        //     'shipping_price' => $data['shipping_price'],
        //     'payment_method' => $data['payment_method'],
        //     'cod_fee' => $data['cod_fee'],
        //     'grand_total' => $data['grand_total'],
        // ]);
        //
        // return $order;

        throw new \Exception('Order creation not implemented - see stub comment');
    }
}
