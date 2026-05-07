<?php

namespace Webkul\PayPay\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Webkul\Checkout\Facades\Cart;
use Webkul\PayPay\Services\PaymentManagerService;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Transformers\OrderResource;

class PayPayController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository,
        protected PaymentManagerService $manager,
    ) {}

    public function callback(Request $request)
    {
        $cartId = $request->get('cart');
        $mpid = session('paypay_mpid_'.$cartId);
        $channel = core()->getCurrentChannelCode();

        if (! $mpid) {
            return $this->fail('paypay::app.payment-failed');
        }

        $verified = $this->manager->verifyPayment($mpid, $channel);

        if ($verified['normalized_status'] !== 'captured') {
            return $this->fail('paypay::app.payment-failed');
        }

        if ($this->manager->isDuplicate($verified['transaction_id'])) {
            return redirect()->route('shop.checkout.onepage.success');
        }

        $cart = Cart::getCart();
        if (! $cart) {
            return $this->fail('paypay::app.payment-failed');
        }

        $data = (new OrderResource($cart))->jsonSerialize();
        $order = $this->orderRepository->create($data);

        $bagistoStatus = $this->manager->bagistoOrderStatus($verified['normalized_status'], $channel);
        $this->orderRepository->update(['status' => $bagistoStatus], $order->id);

        if ($order->canInvoice()) {
            $this->invoiceRepository->create($this->prepareInvoiceData($order));
        }

        Event::dispatch('checkout.order.save.after', $order);

        Cart::deActivateCart();
        session()->flash('order_id', $order->id);
        session()->forget('paypay_mpid_'.$cartId);

        return redirect()->route('shop.checkout.onepage.success');
    }

    public function cancel()
    {
        session()->flash('warning', trans('paypay::app.payment-canceled'));

        return redirect()->route('shop.checkout.cart.index');
    }

    public function webhook(Request $request)
    {
        $channel = core()->getCurrentChannelCode();
        $raw = $request->getContent();
        $signature = $request->header('X-PAYPAY-SIGNATURE', '');
        $timestamp = (int) $request->header('X-PAYPAY-TIMESTAMP', 0) ?: null;

        if (! $this->manager->verifyWebhook($raw, $signature, $timestamp, $channel)) {
            Log::warning('paypay.webhook.invalid_signature', ['channel' => $channel]);

            return response()->json(['error' => 'invalid signature'], 400);
        }

        $payload = json_decode($raw, true) ?: [];
        $txId = $payload['payment_id'] ?? ($payload['data']['paymentId'] ?? null);
        $mpid = $payload['merchant_payment_id'] ?? ($payload['data']['merchantPaymentId'] ?? null);
        $normalized = $this->manager->normalizeStatus($payload['state'] ?? ($payload['data']['status'] ?? null));

        Log::info('paypay.webhook.received', [
            'tx_id' => $txId,
            'mpid' => $mpid,
            'status' => $normalized,
            'channel' => $channel,
        ]);

        if ($this->manager->isDuplicate($txId)) {
            return response()->json(['ok' => true, 'duplicate' => true]);
        }

        $verified = $this->manager->verifyPayment((string) $mpid, $channel);

        if ($verified['normalized_status'] !== $normalized) {
            Log::warning('paypay.webhook.status_mismatch', [
                'webhook' => $normalized,
                'api' => $verified['normalized_status'],
            ]);
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
