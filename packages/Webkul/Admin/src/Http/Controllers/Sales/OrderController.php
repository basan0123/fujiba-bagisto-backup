<?php

namespace Webkul\Admin\Http\Controllers\Sales;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Webkul\Admin\DataGrids\Sales\OrderDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Resources\AddressResource;
use Webkul\Admin\Http\Resources\CartResource;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Repositories\CartRepository;
use Webkul\Customer\Repositories\CustomerGroupRepository;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderCommentRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\ShipmentRepository;
use Webkul\Sales\Transformers\OrderResource;

class OrderController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected OrderCommentRepository $orderCommentRepository,
        protected CartRepository $cartRepository,
        protected CustomerGroupRepository $customerGroupRepository,
        protected InvoiceRepository $invoiceRepository,
        protected ShipmentRepository $shipmentRepository,
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(OrderDataGrid::class)->process();
        }

        $groups = $this->customerGroupRepository->findWhere([['code', '<>', 'guest']]);

        return view('admin::sales.orders.index', compact('groups'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create(int $cartId)
    {
        $cart = $this->cartRepository->find($cartId);

        if (! $cart) {
            return redirect()->route('admin.sales.orders.index');
        }

        $addresses = AddressResource::collection($cart->customer->addresses);

        $cart = new CartResource($cart);

        return view('admin::sales.orders.create', compact('cart', 'addresses'));
    }

    /**
     * Store order
     */
    public function store(int $cartId)
    {
        $cart = $this->cartRepository->findOrFail($cartId);

        Cart::setCart($cart);

        if (Cart::hasError()) {
            return response()->json([
                'message' => trans('admin::app.sales.orders.create.error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        Cart::collectTotals();

        try {
            $this->validateOrder();
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $cart = Cart::getCart();

        if (! in_array($cart->payment->method, ['cashondelivery', 'moneytransfer'])) {
            return response()->json([
                'message' => trans('admin::app.sales.orders.create.payment-not-supported'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $data = (new OrderResource($cart))->jsonSerialize();

        $order = $this->orderRepository->create($data);

        Cart::removeCart($cart);

        session()->flash('order', trans('admin::app.sales.orders.create.order-placed-success'));

        return new JsonResource([
            'redirect' => true,
            'redirect_url' => route('admin.sales.orders.view', $order->id),
        ]);
    }

    /**
     * Show the view for the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function view(int $id)
    {
        $order = $this->orderRepository->findOrFail($id);

        return view('admin::sales.orders.view', compact('order'));
    }

    /**
     * Simple cart-style receipt for fabric checking — no prices, qty-desc.
     *
     * @return \Illuminate\View\View
     */
    public function receipt(int $id)
    {
        $order = $this->orderRepository->findOrFail($id);

        return view('admin::sales.orders.receipt', compact('order'));
    }

    /**
     * Reorder action for the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function reorder(int $id)
    {
        $order = $this->orderRepository->findOrFail($id);

        $cart = Cart::createCart([
            'customer' => $order->customer,
            'is_active' => false,
        ]);

        Cart::setCart($cart);

        foreach ($order->items as $item) {
            try {
                Cart::addProduct($item->product, $item->additional);
            } catch (\Exception $e) {
                // do nothing
            }
        }

        return redirect()->route('admin.sales.orders.create', $cart->id);
    }

    /**
     * Cancel action for the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function cancel(int $id)
    {
        $result = $this->orderRepository->cancel($id);

        if ($result) {
            session()->flash('success', trans('admin::app.sales.orders.view.cancel-success'));
        } else {
            session()->flash('error', trans('admin::app.sales.orders.view.create-error'));
        }

        return redirect()->route('admin.sales.orders.view', $id);
    }

    /**
     * Quick-ship: invoice all remaining qty + ship all remaining qty in one click.
     * Skips the multi-step admin flow for shops that don't issue per-order invoices.
     */
    public function quickShip(int $id)
    {
        $order = $this->orderRepository->findOrFail($id);

        if (! $order->canShip()) {
            session()->flash('error', '発送できる商品がありません。');

            return redirect()->back();
        }

        $invoiceItems = [];
        $shipmentItems = [];

        foreach ($order->items as $item) {
            if ($item->qty_to_invoice > 0) {
                $invoiceItems[$item->id] = (int) $item->qty_to_invoice;
            }
        }

        $sourceId = (int) (\Webkul\Inventory\Models\InventorySource::query()
            ->where('status', 1)
            ->orderBy('priority')
            ->orderBy('id')
            ->value('id'));

        if (! $sourceId) {
            session()->flash('error', '在庫ソースが設定されていません。');

            return redirect()->back();
        }

        try {
            DB::transaction(function () use ($order, $invoiceItems, $sourceId) {
                if (! empty($invoiceItems) && $order->canInvoice()) {
                    $this->invoiceRepository->create([
                        'order_id' => $order->id,
                        'invoice'  => ['items' => $invoiceItems],
                    ]);

                    $order->refresh();
                }

                $shipmentItems = [];

                foreach ($order->items as $item) {
                    if ($item->qty_to_ship > 0) {
                        $shipmentItems[$item->id] = [$sourceId => (int) $item->qty_to_ship];
                    }
                }

                if (empty($shipmentItems)) {
                    return;
                }

                $this->shipmentRepository->create([
                    'order_id' => $order->id,
                    'shipment' => [
                        'source'        => $sourceId,
                        'carrier_title' => '佐川急便',
                        'track_number'  => '',
                        'items'         => $shipmentItems,
                    ],
                ]);
            });
        } catch (\Throwable $e) {
            \Log::error('quickShip failed for order ' . $id . ': ' . $e->getMessage());

            session()->flash('error', '発送処理に失敗しました: ' . $e->getMessage());

            return redirect()->back();
        }

        $this->orderCommentRepository->create([
            'order_id' => $order->id,
            'comment'  => '管理者により「発送済み」に変更されました（請求書自動作成）。',
        ]);

        session()->flash('success', '注文を発送済みにしました。');

        return redirect()->route('admin.sales.orders.view', $id);
    }

    /**
     * Add comment to the order
     *
     * @return \Illuminate\Http\Response
     */
    public function comment(int $id)
    {
        $validatedData = $this->validate(request(), [
            'comment' => 'required',
            'customer_notified' => 'sometimes|sometimes',
        ]);

        $validatedData['order_id'] = $id;

        Event::dispatch('sales.order.comment.create.before');

        $comment = $this->orderCommentRepository->create($validatedData);

        Event::dispatch('sales.order.comment.create.after', $comment);

        session()->flash('success', trans('admin::app.sales.orders.view.comment-success'));

        return redirect()->route('admin.sales.orders.view', $id);
    }

    /**
     * Result of search product.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function search()
    {
        $orders = $this->orderRepository->scopeQuery(function ($query) {
            return $query->where('customer_email', 'like', '%'.urldecode(request()->input('query')).'%')
                ->orWhere('status', 'like', '%'.urldecode(request()->input('query')).'%')
                ->orWhere(DB::raw('CONCAT('.DB::getTablePrefix().'customer_first_name, " ", '.DB::getTablePrefix().'customer_last_name)'), 'like', '%'.urldecode(request()->input('query')).'%')
                ->orWhere('increment_id', request()->input('query'))
                ->orderBy('created_at', 'desc');
        })->paginate(10);

        foreach ($orders as $key => $order) {
            $orders[$key]['formatted_created_at'] = core()->formatDate($order->created_at, 'd M Y');

            $orders[$key]['status_label'] = $order->status_label;

            $orders[$key]['customer_full_name'] = $order->customer_full_name;
        }

        return response()->json($orders);
    }

    /**
     * Switch payment method on an existing order (COD <-> Bank Transfer).
     * Automatically adds/removes COD surcharge.
     */
    public function switchPaymentMethod(int $id)
    {
        $order = $this->orderRepository->findOrFail($id);
        $newMethod = request()->input('payment_method');

        if (! in_array($newMethod, ['cashondelivery', 'moneytransfer'])) {
            return response()->json([
                'message' => '対応していない支払方法です。',
            ], Response::HTTP_BAD_REQUEST);
        }

        $currentMethod = $order->payment->method ?? null;

        if ($currentMethod === $newMethod) {
            return response()->json([
                'message' => '同じ支払方法です。',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Determine COD fee based on order subtotal (before any existing COD fee)
        $baseAmount = $order->base_grand_total - $order->base_cod_fee;
        $codFeeUnder = (float) (core()->getConfigData('sales.payment_methods.cashondelivery.cod_fee_under_10000') ?: 330);
        $codFeeAbove = (float) (core()->getConfigData('sales.payment_methods.cashondelivery.cod_fee_10000_and_above') ?: 440);
        $codFee = $baseAmount < 10000 ? $codFeeUnder : $codFeeAbove;

        $oldGrandTotal = $order->base_grand_total;

        if ($newMethod === 'cashondelivery') {
            // Switching TO COD: add surcharge
            $order->cod_fee = $codFee;
            $order->base_cod_fee = $codFee;
            $order->grand_total = $baseAmount + $codFee;
            $order->base_grand_total = $baseAmount + $codFee;
        } else {
            // Switching FROM COD: remove surcharge
            $order->cod_fee = 0;
            $order->base_cod_fee = 0;
            $order->grand_total = $baseAmount;
            $order->base_grand_total = $baseAmount;
        }

        $order->save();

        // Update order_payment record
        $order->payment->update([
            'method'       => $newMethod,
            'method_title' => core()->getConfigData('sales.payment_methods.' . $newMethod . '.title') ?: ($newMethod === 'cashondelivery' ? '代金引換' : '銀行振込'),
        ]);

        // Add comment for audit trail
        $methodLabel = $newMethod === 'cashondelivery' ? '代金引換' : '銀行振込';
        $this->orderCommentRepository->create([
            'order_id' => $order->id,
            'comment'  => "支払方法を「{$methodLabel}」に変更しました。" .
                          ($newMethod === 'cashondelivery' ? " 代引手数料: ¥" . number_format($codFee) . " 追加。" : " 代引手数料を除外しました。") .
                          " 合計: ¥" . number_format($order->base_grand_total) .
                          " (旧合計: ¥" . number_format($oldGrandTotal) . ")",
        ]);

        return response()->json([
            'success'         => true,
            'new_method'      => $newMethod,
            'method_label'    => $methodLabel,
            'cod_fee_applied' => $order->base_cod_fee,
            'new_grand_total' => $order->base_grand_total,
            'formatted_total' => core()->formatBasePrice($order->base_grand_total),
        ]);
    }

    /**
     * Send order notification via Messenger and/or Email.
     */
    public function sendNotification(int $id)
    {
        $order = $this->orderRepository->findOrFail($id);

        $notifyMessenger = (bool) request()->input('notify_messenger', false);
        $notifyEmail     = (bool) request()->input('notify_email', false);
        $message         = request()->input('message', '');

        $messengerSent = false;
        $emailSent     = false;

        // Messenger notification via Chatpion. The customer's Messenger
        // subscriber ID is stored on the customer record itself
        // (customers.chatpion_subscribe_id), and per-channel Chatpion
        // credentials live in `livechat_settings`.
        if ($notifyMessenger) {
            try {
                $subscriberId = DB::table('customers')
                    ->where('id', $order->customer_id)
                    ->value('chatpion_subscribe_id');

                if ($subscriberId) {
                    $tenantId = function_exists('tenant') && tenant() ? tenant('id') : (auth()->user()->tenant_id ?? '');
                    $setting  = \Fujiba\LiveChat\Models\LiveChatSetting::where('tenant_id', $tenantId)
                        ->where('channel_id', $order->channel_id)
                        ->first();

                    if ($setting && $setting->livechat_enabled && $setting->order_notifications_enabled) {
                        $chatpion = new \Fujiba\LiveChat\Services\ChatpionFailoverService($setting);

                        $chatpion->post('api/send_bagisto_notification', [
                            'api_key'       => $setting->chatpion_api_key,
                            'subscriber_id' => $subscriberId,
                            'order_id'      => $order->increment_id,
                            'status'        => $order->status,
                            'message'       => $message,
                            'tracking_no'   => $order->shipments->first()?->track_number ?? '',
                            'btn_url'       => route('shop.customers.account.orders.view', $order->id),
                        ]);

                        $messengerSent = true;
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Order notification messenger failed: ' . $e->getMessage());
            }
        }

        // Email notification
        if ($notifyEmail && $order->customer_email) {
            try {
                $senderDetails = core()->getSenderEmailDetails();

                Mail::raw($message, function ($mail) use ($order, $senderDetails) {
                    $mail->to($order->customer_email, $order->customer_full_name)
                         ->from($senderDetails['email'], $senderDetails['name'])
                         ->subject("ご注文 #{$order->increment_id} のお知らせ");
                });

                $emailSent = true;
            } catch (\Exception $e) {
                \Log::warning('Order notification email failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success'        => true,
            'messenger_sent' => $messengerSent,
            'email_sent'     => $emailSent,
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

        if (! Cart::haveMinimumOrderAmount()) {
            throw new \Exception(trans('admin::app.sales.orders.create.minimum-order-error', [
                'amount' => core()->formatPrice(core()->getConfigData('sales.order_settings.minimum_order.minimum_order_amount') ?: 0),
            ]));
        }

        if (
            $cart->haveStockableItems()
            && ! $cart->shipping_address
        ) {
            throw new \Exception(trans('admin::app.sales.orders.create.check-shipping-address'));
        }

        if (! $cart->billing_address) {
            throw new \Exception(trans('admin::app.sales.orders.create.check-billing-address'));
        }

        if (
            $cart->haveStockableItems()
            && ! $cart->selected_shipping_rate
        ) {
            throw new \Exception(trans('admin::app.sales.orders.create.specify-shipping-method'));
        }

        if (! $cart->payment) {
            throw new \Exception(trans('admin::app.sales.orders.create.specify-payment-method'));
        }
    }
}
