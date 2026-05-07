<?php

namespace Webkul\Shop\Http\Controllers\API;

use App\Services\KimonoChannelRules;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Webkul\Checkout\Facades\Cart;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Payment\Facades\Payment;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Transformers\OrderResource;
use Webkul\Shipping\Facades\Shipping;
use Webkul\Shop\Http\Requests\CartAddressRequest;
use Webkul\Shop\Http\Resources\CartResource;

class OnepageController extends APIController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected CustomerRepository $customerRepository
    ) {}

    /**
     * Return cart summary.
     */
    public function summary(): JsonResource
    {
        // Recompute totals before returning so any rules that fire on
        // collect.totals.after (bulk-bonus tier discounts, percent-off,
        // free-items) are reflected on the onepage summary. The cart-page
        // API does the same in CartController::index — without this call
        // the onepage summary returns whatever was in the cart row from
        // the previous page load, missing newly-applicable promotions.
        Cart::collectTotals();

        $cart = Cart::getCart();

        return new CartResource($cart);
    }

    /**
     * Store address.
     */
    public function storeAddress(CartAddressRequest $cartAddressRequest): JsonResource
    {
        $params = $cartAddressRequest->all();

        if (
            ! auth()->guard('customer')->check()
            && ! Cart::getCart()->hasGuestCheckoutItems()
        ) {
            return new JsonResource([
                'redirect' => true,
                'data' => route('shop.customer.session.index'),
            ]);
        }

        if (Cart::hasError()) {
            return new JsonResource([
                'redirect' => true,
                'redirect_url' => route('shop.checkout.cart.index'),
            ]);
        }

        // Messenger-logged-in customers may leave the email field blank on
        // mmfood/kimono. Fall back to the stored customer email so order
        // creation, invoice emails, and payment receipts always have one.
        $authCustomer = auth()->guard('customer')->user();
        if ($authCustomer && ! empty($authCustomer->email)) {
            foreach (['billing', 'shipping'] as $addrType) {
                if (isset($params[$addrType]) && is_array($params[$addrType]) && empty($params[$addrType]['email'])) {
                    $params[$addrType]['email'] = $authCustomer->email;
                }
            }
        }

        // Same treatment for phone: Messenger customers may not have one. Fall
        // back to the customer record, otherwise leave blank — the address row
        // accepts NULL phone for these channels.
        if ($authCustomer && ! empty($authCustomer->phone)) {
            foreach (['billing', 'shipping'] as $addrType) {
                if (isset($params[$addrType]) && is_array($params[$addrType]) && empty($params[$addrType]['phone'])) {
                    $params[$addrType]['phone'] = $authCustomer->phone;
                }
            }
        }

        Cart::saveAddresses($params);

        $cart = Cart::getCart();

        Cart::collectTotals();

        if ($cart->haveStockableItems()) {
            if (! $rates = Shipping::collectRates()) {
                return new JsonResource([
                    'redirect' => true,
                    'redirect_url' => route('shop.checkout.cart.index'),
                ]);
            }

            return new JsonResource([
                'redirect' => false,
                'data' => $rates,
            ]);
        }

        return new JsonResource([
            'redirect' => false,
            'data' => Payment::getSupportedPaymentMethods(),
        ]);
    }

    /**
     * Store shipping method and return payment methods with COD fee info.
     */
    public function storeShippingMethod()
    {
        $validatedData = $this->validate(request(), [
            'shipping_method' => 'required',
        ]);

        if (Cart::hasError()) {
            return response()->json([
                'redirect_url' => route('shop.checkout.cart.index'),
            ], Response::HTTP_FORBIDDEN);
        }

        if (! Cart::saveShippingMethod($validatedData['shipping_method'])) {
            return response()->json([
                'redirect_url' => route('shop.checkout.cart.index'),
            ], Response::HTTP_FORBIDDEN);
        }

        Cart::collectTotals();

        $cart = Cart::getCart();
        $paymentMethods = Payment::getSupportedPaymentMethods();
        $codFee = $this->calculateCODFee($cart);

        $totalQty = (int) ($cart->items_qty ?? $cart->items->sum('quantity'));
        $codMinQty = (int) core()->getConfigData('sales.payment_methods.cashondelivery.cod_min_qty') ?: 0;

        if ($codMinQty > 0 && $totalQty < $codMinQty) {
            $paymentMethods['payment_methods'] = array_values(
                array_filter($paymentMethods['payment_methods'] ?? [], fn ($m) => ($m['method'] ?? null) !== 'cashondelivery')
            );
        } else {
            foreach ($paymentMethods['payment_methods'] ?? [] as &$method) {
                if (($method['method'] ?? null) === 'cashondelivery') {
                    $method['cod_fee'] = $codFee;
                    $method['cod_fee_formatted'] = '¥' . number_format($codFee, 0);
                }
            }
            unset($method);
        }

        if ($constraint = KimonoChannelRules::constraintForCart($cart)) {
            $allowed = $constraint['allowed_payment_methods'];
            $paymentMethods['payment_methods'] = array_values(
                array_filter(
                    $paymentMethods['payment_methods'] ?? [],
                    fn ($m) => in_array($m['method'] ?? null, $allowed, true)
                )
            );
        }

        return response()->json([
            'payment_methods' => $paymentMethods,
            'cart' => new CartResource($cart),
        ]);
    }

    /**
     * Calculate COD fee from admin payment-method settings (core_config).
     */
    private function calculateCODFee($cart): float
    {
        $total = (float) ($cart->sub_total ?? 0) + (float) ($cart->shipping_amount ?? 0);

        $feeUnder = (float) core()->getConfigData('sales.payment_methods.cashondelivery.cod_fee_under_10000') ?: 0;
        $feeAbove = (float) core()->getConfigData('sales.payment_methods.cashondelivery.cod_fee_10000_and_above') ?: 0;

        return $total >= 10000 ? $feeAbove : $feeUnder;
    }

    /**
     * Store payment method and return cart with COD fee if applicable.
     */
    public function storePaymentMethod()
    {
        $validatedData = $this->validate(request(), [
            'payment' => 'required',
        ]);

        if (
            Cart::hasError()
            || ! $validatedData['payment']
            || ! Cart::savePaymentMethod($validatedData['payment'])
        ) {
            return response()->json([
                'redirect_url' => route('shop.checkout.cart.index'),
            ], Response::HTTP_FORBIDDEN);
        }

        Cart::collectTotals();

        $cart    = Cart::getCart();
        $method  = is_array($validatedData['payment'])
            ? ($validatedData['payment']['method'] ?? '')
            : $validatedData['payment'];
        $codFee  = $method === 'cashondelivery' ? $this->calculateCODFee($cart) : 0;

        return response()->json([
            'cart'    => new CartResource($cart),
            'cod_fee' => (float) $codFee,
            'cod_fee_formatted' => $codFee > 0 ? ('¥' . number_format($codFee, 0)) : '',
        ]);
    }

    /**
     * Get COD Notice for current channel and locale
     */
    public function getCodNotice()
    {
        $channel = core()->getCurrentChannelCode();
        $locale = app()->getLocale() ?? 'en';
        
        $notice = config("checkout.cod_notice.{$channel}.{$locale}")
            ?? config("checkout.cod_notice.default.{$locale}")
            ?? config("checkout.cod_notice.default.en")
            ?? "Cash on Delivery: A fee will be added to your order. Payment collected at delivery.";

        \Log::info('[Myanmar Food Store] COD Notice retrieved', [
            'channel' => $channel,
            'locale' => $locale,
        ]);

        return response()->json([
            'notice' => $notice,
            'channel' => $channel,
            'locale' => $locale,
        ]);
    }

    /**
     * Store order
     */
    public function storeOrder()
    {
        if (Cart::hasError()) {
            return new JsonResource([
                'redirect' => true,
                'redirect_url' => route('shop.checkout.cart.index'),
            ]);
        }

        Cart::collectTotals();

        try {
            $this->validateOrder();
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }

        $cart = Cart::getCart();

        if ($redirectUrl = Payment::getRedirectUrl($cart)) {
            return new JsonResource([
                'redirect' => true,
                'redirect_url' => $redirectUrl,
            ]);
        }

        $data = (new OrderResource($cart))->jsonSerialize();

        $codFee = ($cart->payment?->method === 'cashondelivery')
            ? $this->calculateCODFee($cart)
            : 0;

        if ($codFee > 0) {
            $data['cod_fee']           = $codFee;
            $data['base_cod_fee']      = $codFee;
            $data['grand_total']       = (float) ($data['grand_total'] ?? 0) + $codFee;
            $data['base_grand_total']  = (float) ($data['base_grand_total'] ?? 0) + $codFee;
        }

        $order = $this->orderRepository->create($data);

        Cart::deActivateCart();

        session()->flash('order_id', $order->id);

        return new JsonResource([
            'redirect' => true,
            'redirect_url' => route('shop.checkout.onepage.success'),
        ]);
    }

    /**
     * Validate order before creation.
     *
     * @return void|\Exception
     */
    public function validateOrder()
    {
        $cart = Cart::getCart();

        $minimumOrderAmount = core()->getConfigData('sales.order_settings.minimum_order.minimum_order_amount') ?: 0;

        if (
            auth()->guard('customer')->check()
            && auth()->guard('customer')->user()->is_suspended
        ) {
            throw new \Exception(trans('shop::app.checkout.cart.suspended-account-message'));
        }

        if (
            auth()->guard('customer')->user()
            && ! auth()->guard('customer')->user()->status
        ) {
            throw new \Exception(trans('shop::app.checkout.cart.inactive-account-message'));
        }

        if (! Cart::haveMinimumOrderAmount()) {
            throw new \Exception(trans('shop::app.checkout.cart.minimum-order-message', ['amount' => core()->currency($minimumOrderAmount)]));
        }

        if ($constraint = KimonoChannelRules::constraintForCart($cart)) {
            $method = $cart->payment?->method;
            if ($method && ! in_array($method, $constraint['allowed_payment_methods'], true)) {
                throw new \Exception(trans('shop::app.checkout.onepage.delivery.kimono_payment_not_allowed'));
            }
        }

        if ($cart->haveStockableItems() && ! $cart->shipping_address) {
            throw new \Exception(trans('shop::app.checkout.onepage.address.check-shipping-address'));
        }

        if (! $cart->billing_address) {
            throw new \Exception(trans('shop::app.checkout.onepage.address.check-billing-address'));
        }

        if (
            $cart->haveStockableItems()
            && ! $cart->selected_shipping_rate
        ) {
            throw new \Exception(trans('shop::app.checkout.cart.specify-shipping-method'));
        }

        if (! $cart->payment) {
            throw new \Exception(trans('shop::app.checkout.cart.specify-payment-method'));
        }
    }
}
