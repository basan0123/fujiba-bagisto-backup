<?php

namespace Webkul\Komoju\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\Cart as CartModel;
use Webkul\Komoju\Services\PaymentManagerService;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Transformers\OrderResource;

class KomojuController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository,
        protected PaymentManagerService $manager,
    ) {}

    public function callback(Request $request)
    {
        $cartId  = $request->get('cart');
        $channel = core()->getCurrentChannelCode();

        Log::info('komoju.callback.start', [
            'cart'    => $cartId,
            'channel' => $channel,
            'query'   => $request->query(),
        ]);

        if (! $this->manager->verifyReturnUrl($request, $channel)) {
            Log::warning('komoju.callback.invalid_hmac', ['cart' => $cartId]);

            return $this->fail('komoju::app.payment-failed');
        }

        $sessionId = $request->query('session_id') ?: session('komoju_session_id_'.$cartId);
        if (! $sessionId) {
            Log::warning('komoju.callback.no_session_id', ['cart' => $cartId]);

            return $this->fail('komoju::app.payment-failed');
        }

        $verified = $this->manager->verifySession($sessionId, $channel);

        Log::info('komoju.callback.verified', [
            'cart'              => $cartId,
            'normalized_status' => $verified['normalized_status'] ?? null,
            'transaction_id'    => $verified['transaction_id']    ?? null,
        ]);

        if (($verified['normalized_status'] ?? null) !== 'captured') {
            return $this->fail('komoju::app.payment-failed');
        }

        if ($this->manager->isDuplicate($verified['transaction_id'])) {
            Log::info('komoju.callback.duplicate', ['tx' => $verified['transaction_id']]);

            return redirect()->route('shop.checkout.onepage.success');
        }

        // Load cart independently of browser session — cross-domain redirects
        // from Komoju can arrive without the session cookie. The return URL
        // carries ?cart={id} and Komoju's session metadata also contains it.
        $cart = $this->resolveCart($cartId, $verified);

        if (! $cart) {
            Log::error('komoju.callback.no_cart', [
                'cart_id' => $cartId,
                'session' => $sessionId,
            ]);

            return $this->fail('komoju::app.payment-failed');
        }

        // Rehydrate the Cart facade so OrderResource can build the order.
        Cart::setCart($cart);

        $data  = (new OrderResource($cart))->jsonSerialize();
        $order = $this->orderRepository->create($data);

        $status = $this->manager->bagistoOrderStatus($verified['normalized_status'], $channel);
        $this->orderRepository->update(['status' => $status], $order->id);

        if ($order->canInvoice()) {
            $this->invoiceRepository->create($this->prepareInvoiceData($order));
        }

        Event::dispatch('checkout.order.save.after', $order);

        // Mark cart inactive directly on the model — Cart::deActivateCart()
        // depends on the currently-loaded cart matching the session.
        $cart->update(['is_active' => 0]);

        session()->flash('order_id', $order->id);
        session()->forget('komoju_session_id_'.$cartId);

        Log::info('komoju.callback.success', [
            'order_id' => $order->id,
            'cart_id'  => $cart->id,
        ]);

        return redirect()->route('shop.checkout.onepage.success');
    }

    /**
     * Resolve the cart without depending on the browser session. Komoju's
     * return URL carries ?cart={id}; we also cross-check the cart_id embedded
     * in Komoju's verified session metadata.
     */
    protected function resolveCart($cartId, array $verified): ?CartModel
    {
        $candidateIds = array_filter([
            $cartId,
            $verified['metadata']['cart_id'] ?? null,
        ]);

        foreach ($candidateIds as $id) {
            $cart = CartModel::query()->find($id);
            if ($cart) {
                return $cart;
            }
        }

        return null;
    }

    public function cancel()
    {
        session()->flash('warning', trans('komoju::app.payment-canceled'));

        return redirect()->route('shop.checkout.cart.index');
    }

    public function webhook(Request $request)
    {
        $channel = core()->getCurrentChannelCode();
        $raw = $request->getContent();
        $signature = $request->header('X-Komoju-Signature', '');
        $timestamp = (int) $request->header('X-Komoju-Timestamp', 0) ?: null;

        if (! $this->manager->verifyWebhook($raw, $signature, $timestamp, $channel)) {
            Log::warning('komoju.webhook.invalid_signature', ['channel' => $channel]);

            return response()->json(['error' => 'invalid signature'], 400);
        }

        $payload = json_decode($raw, true) ?: [];
        $eventName = $payload['type'] ?? ($payload['event'] ?? null);
        $data = $payload['data'] ?? [];
        $txId = $data['id'] ?? ($data['payment_id'] ?? null);
        $orderId = $data['external_order_num'] ?? ($data['metadata']['order_id'] ?? null);
        $normalized = $this->manager->normalizeEvent($eventName);

        Log::info('komoju.webhook.received', [
            'event' => $eventName,
            'tx_id' => $txId,
            'order_id' => $orderId,
            'normalized' => $normalized,
            'channel' => $channel,
        ]);

        if ($this->manager->isDuplicate($txId)) {
            return response()->json(['ok' => true, 'duplicate' => true]);
        }

        if (! $orderId) {
            return response()->json(['ok' => true]);
        }

        $order = $this->orderRepository->find($orderId);
        if (! $order) {
            return response()->json(['ok' => true]);
        }

        $bagistoStatus = $this->manager->bagistoOrderStatus($normalized, $channel);

        switch ($normalized) {
            case 'captured':
                $this->orderRepository->update(['status' => $bagistoStatus], $order->id);
                if ($order->canInvoice()) {
                    $this->invoiceRepository->create($this->prepareInvoiceData($order));
                }
                Event::dispatch('checkout.order.save.after', $order);
                break;

            case 'refunded':
            case 'failed':
            case 'expired':
                $this->orderRepository->update(['status' => $bagistoStatus], $order->id);
                break;

            case 'pending':
            default:
                break;
        }

        return response()->json(['ok' => true]);
    }

    protected function fail(string $key)
    {
        session()->flash('error', trans($key));

        return redirect()->route('shop.checkout.cart.index');
    }

    protected function prepareInvoiceData($order): array
    {
        $items = [];
        foreach ($order->items as $item) {
            $items[$item->id] = $item->qty_to_invoice;
        }

        return ['order_id' => $order->id, 'invoice' => ['items' => $items]];
    }
}
