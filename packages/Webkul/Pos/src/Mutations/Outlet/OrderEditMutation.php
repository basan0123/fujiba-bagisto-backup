<?php

namespace Webkul\Pos\Mutations\Outlet;

use Fujiba\OrdersAdmin\Exceptions\OrderEditException;
use Fujiba\OrdersAdmin\Services\FulfillmentRecalculator;
use Fujiba\OrdersAdmin\Services\OrderAddressUpdater;
use Fujiba\OrdersAdmin\Services\OrderLabelPrinter;
use Fujiba\OrdersAdmin\Services\OrderNoteWriter;
use Fujiba\OrdersAdmin\Services\OrderPaymentConfirmer;
use Fujiba\OrdersAdmin\Services\OrderPaymentMethodUpdater;
use Fujiba\OrdersAdmin\Services\OrderStatusUpdater;
use Fujiba\OrdersAdmin\Services\OrderTrackingUpdater;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\GraphQLAPI\Validators\CustomException;
use Webkul\Pos\Helpers\RoleGate;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderRepository;

/**
 * Order-edit mutations for the native POS — the GraphQL face of the admin
 * "advanced order" screen. Every mutation delegates to the SAME shared
 * services the admin REST controllers use (Fujiba\OrdersAdmin\Services\*) so
 * the business rules can never drift between the two surfaces.
 *
 * Auth: cashier+ (mirrors the admin screen's sales.orders.edit — any logged-in
 * POS user). Scope: an agent may only touch an order on their own outlet's
 * channel; resolveScopedOrder() enforces this and 404s otherwise.
 *
 * This class covers address / payment-method / fulfillment / status / note.
 * Line-item editing lives in OrderItemEditMutation (separate, heavier).
 */
class OrderEditMutation
{
    /** Payment methods the admin screen allows (UpdateOrderPaymentMethodRequest). */
    private const PAYMENT_METHODS = ['cashondelivery', 'moneytransfer', 'cash', 'kpay'];

    /** Fulfillment types the recalculator understands (UpdateOrderFulfillmentRequest). */
    private const FULFILLMENT_TYPES = ['delivery', 'pickup', 'cod', 'bank_transfer'];

    /** Order statuses the admin screen allows (UpdateOrderStatusRequest). */
    private const STATUSES = [
        Order::STATUS_PENDING,
        Order::STATUS_PENDING_PAYMENT,
        Order::STATUS_PROCESSING,
        Order::STATUS_COMPLETED,
        Order::STATUS_CANCELED,
        Order::STATUS_CLOSED,
        Order::STATUS_FRAUD,
    ];

    public function __construct(
        protected OrderRepository $orderRepository,
        protected OrderAddressUpdater $addressUpdater,
        protected OrderPaymentMethodUpdater $paymentMethodUpdater,
        protected OrderStatusUpdater $statusUpdater,
        protected OrderNoteWriter $noteWriter,
        protected FulfillmentRecalculator $fulfillmentRecalculator,
        protected OrderLabelPrinter $labelPrinter,
        protected OrderPaymentConfirmer $paymentConfirmer,
        protected OrderTrackingUpdater $trackingUpdater,
    ) {}

    /* ---------------------------------------------------------------- */
    /* Shipping address                                                  */
    /* ---------------------------------------------------------------- */

    public function updateAddress($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireCashier($context->user());

        $validator = Validator::make($args, [
            'orderId'  => 'required|integer',
            'name'     => 'required|string|max:255',
            'address1' => 'required|string|max:255',
            'address2' => 'nullable|string|max:255',
            'city'     => 'required|string|max:100',
            'state'    => 'nullable|string|max:100',
            'postcode' => 'required|string|max:20',
            'country'  => 'required|string|size:2',
            'phone'    => 'nullable|string|max:30',
        ]);
        $this->assertValid($validator);

        $order = $this->resolveScopedOrder($args['orderId'], $context, ['addresses']);

        return $this->guard(function () use ($order, $args) {
            $this->addressUpdater->update($order, [
                'name'     => $args['name'],
                'address1' => $args['address1'],
                'address2' => $args['address2'] ?? null,
                'city'     => $args['city'],
                'state'    => $args['state'] ?? '',
                'postcode' => $args['postcode'],
                'country'  => $args['country'],
                'phone'    => $args['phone'] ?? null,
            ]);

            return $this->ok(trans('pos::app.outlet.orders.address-updated'));
        });
    }

    /* ---------------------------------------------------------------- */
    /* Payment method                                                    */
    /* ---------------------------------------------------------------- */

    public function updatePaymentMethod($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireCashier($context->user());

        $validator = Validator::make($args, [
            'orderId' => 'required|integer',
            'method'  => 'required|string|in:'.implode(',', self::PAYMENT_METHODS),
        ]);
        $this->assertValid($validator);

        $order = $this->resolveScopedOrder($args['orderId'], $context, ['payment']);

        return $this->guard(function () use ($order, $args) {
            $result = $this->paymentMethodUpdater->update($order, $args['method']);

            return array_merge(
                $this->ok(trans('pos::app.outlet.orders.payment-updated')),
                [
                    'codFeeApplied' => $result['cod_fee_applied'],
                    'newGrandTotal' => $result['new_grand_total'],
                ],
            );
        });
    }

    /* ---------------------------------------------------------------- */
    /* Fulfillment type (+ non-persisting preview)                       */
    /* ---------------------------------------------------------------- */

    public function previewFulfillment($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireCashier($context->user());

        $type = $this->validatedType($args);
        $order = $this->resolveScopedOrder($args['orderId'], $context, ['items']);

        $next = $this->fulfillmentRecalculator->compute($order, $type);

        return [
            'type'          => $type,
            'shippingAmount' => (float) $next['shipping_amount'],
            'codFee'        => (float) $next['cod_fee'],
            'coolSurcharge' => (float) $next['cool_surcharge'],
            'grandTotal'    => (float) $next['grand_total'],
            'needsCool'     => $this->fulfillmentRecalculator->orderNeedsCoolShipping($order),
        ];
    }

    public function updateFulfillment($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireCashier($context->user());

        $type = $this->validatedType($args);
        $order = $this->resolveScopedOrder($args['orderId'], $context, ['items']);

        return $this->guard(function () use ($order, $type) {
            $order->update($this->fulfillmentRecalculator->compute($order, $type));

            return $this->ok(trans('pos::app.outlet.orders.fulfillment-updated'));
        });
    }

    /* ---------------------------------------------------------------- */
    /* Order status (cancel runs the full stock-return flow)             */
    /* ---------------------------------------------------------------- */

    public function updateStatus($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireCashier($context->user());

        $validator = Validator::make($args, [
            'orderId'         => 'required|integer',
            'status'          => 'required|string|in:'.implode(',', self::STATUSES),
            'customerMessage' => 'nullable|string|max:5000',
        ]);
        $this->assertValid($validator);

        $order = $this->resolveScopedOrder($args['orderId'], $context, ['items', 'comments']);

        return $this->guard(function () use ($order, $args) {
            $this->statusUpdater->update($order, $args['status'], $args['customerMessage'] ?? null);

            return $this->ok(trans('pos::app.outlet.orders.status-updated'));
        });
    }

    /* ---------------------------------------------------------------- */
    /* Internal admin note                                               */
    /* ---------------------------------------------------------------- */

    public function updateNote($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireCashier($context->user());

        $validator = Validator::make($args, [
            'orderId' => 'required|integer',
            'note'    => 'required|string|max:5000',
        ]);
        $this->assertValid($validator);

        $order = $this->resolveScopedOrder($args['orderId'], $context);

        return $this->guard(function () use ($order, $args) {
            $this->noteWriter->write($order, $args['note']);

            return $this->ok(trans('pos::app.outlet.orders.note-saved'));
        });
    }

    /* ---------------------------------------------------------------- */
    /* Mark label printed (auto-ship unshipped portion + COD confirm)    */
    /* ---------------------------------------------------------------- */

    public function markLabelPrinted($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireCashier($context->user());

        $validator = Validator::make($args, ['orderId' => 'required|integer']);
        $this->assertValid($validator);

        $order = $this->resolveScopedOrder($args['orderId'], $context, ['items', 'payment']);

        return $this->guard(function () use ($order) {
            $result = $this->labelPrinter->flip($order);

            return $this->ok($result['already']
                ? trans('OrdersAdmin::app.label-printed.already')
                : trans('OrdersAdmin::app.label-printed.flipped'));
        });
    }

    /* ---------------------------------------------------------------- */
    /* Confirm / un-confirm payment                                      */
    /* ---------------------------------------------------------------- */

    public function confirmPayment($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireCashier($context->user());

        $validator = Validator::make($args, [
            'orderId'   => 'required|integer',
            'confirmed' => 'required|boolean',
            'note'      => 'nullable|string|max:5000',
        ]);
        $this->assertValid($validator);

        $order = $this->resolveScopedOrder($args['orderId'], $context, ['payment']);
        $confirmed = (bool) $args['confirmed'];

        return $this->guard(function () use ($order, $confirmed, $args) {
            $data = $this->paymentConfirmer->confirm($order, $confirmed, $args['note'] ?? null);

            return array_merge(
                $this->ok($confirmed
                    ? trans('OrdersAdmin::app.payment-confirm.confirmed')
                    : trans('OrdersAdmin::app.payment-confirm.unconfirmed')),
                ['paymentConfirmedAt' => $data['payment_confirmed_at']],
            );
        });
    }

    /* ---------------------------------------------------------------- */
    /* Tracking number (requires an existing shipment)                   */
    /* ---------------------------------------------------------------- */

    public function updateTracking($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireCashier($context->user());

        $validator = Validator::make($args, [
            'orderId'        => 'required|integer',
            'trackingNumber' => 'required|string|max:100',
        ]);
        $this->assertValid($validator);

        $order = $this->resolveScopedOrder($args['orderId'], $context, ['shipments']);

        return $this->guard(function () use ($order, $args) {
            $this->trackingUpdater->update($order, $args['trackingNumber']);

            return $this->ok(trans('OrdersAdmin::app.tracking.updated'));
        });
    }

    /* ================================================================ */
    /* Helpers                                                           */
    /* ================================================================ */

    /**
     * Load an order by id, scoped to the agent's outlet channel. POS register
     * sales (pos_order on this outlet) and storefront orders on this outlet's
     * channel are both editable; anything else 404s so an agent can't edit
     * another store's orders.
     */
    protected function resolveScopedOrder(int $orderId, GraphQLContext $context, array $with = []): Order
    {
        $user      = $context->user();
        $channelId = $user->outlet?->channel_id;

        $order = $this->orderRepository->with($with)->find($orderId);

        $belongsToChannel = $channelId !== null && (int) $order?->channel_id === (int) $channelId;
        $belongsToOutlet  = $order
            && \Illuminate\Support\Facades\DB::table('pos_order')
                ->where('order_id', $order->id)
                ->where('outlet_id', $user->outlet_id)
                ->exists();

        if (! $order || (! $belongsToChannel && ! $belongsToOutlet)) {
            throw new CustomException(trans('pos::app.outlet.orders.not-found'));
        }

        return $order;
    }

    /** Validate + extract the fulfillment type, scoped to a real order id. */
    protected function validatedType(array $args): string
    {
        $validator = Validator::make($args, [
            'orderId' => 'required|integer',
            'type'    => 'required|string|in:'.implode(',', self::FULFILLMENT_TYPES),
        ]);
        $this->assertValid($validator);

        return $args['type'];
    }

    protected function assertValid(\Illuminate\Validation\Validator $validator): void
    {
        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }
    }

    /** Run a service call, mapping OrderEditException to a {success:false} payload. */
    protected function guard(callable $fn): array
    {
        try {
            return $fn();
        } catch (OrderEditException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function ok(string $message): array
    {
        return ['success' => true, 'message' => $message];
    }
}
