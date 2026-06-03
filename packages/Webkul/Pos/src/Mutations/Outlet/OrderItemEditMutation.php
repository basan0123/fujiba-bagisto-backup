<?php

namespace Webkul\Pos\Mutations\Outlet;

use Fujiba\OrdersAdmin\Exceptions\OrderEditException;
use Fujiba\OrdersAdmin\Services\OrderItemEditor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\GraphQLAPI\Validators\CustomException;
use Webkul\Pos\Helpers\RoleGate;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderRepository;

/**
 * Line-item editing for the native POS — add / remove / change-qty on a
 * pending/processing order. The GraphQL face of the admin advanced screen's
 * item editor; delegates to the shared Fujiba\OrdersAdmin\Services\
 * OrderItemEditor so the recompute (stock pre-flight, tax, COD/cool, discount
 * scaling, inventory reindex) is identical to the admin path.
 *
 * Cashier+; scoped to the agent's outlet channel.
 */
class OrderItemEditMutation
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected OrderItemEditor $itemEditor,
    ) {}

    /**
     * editOrderItems(orderId, items:[{productId, qty}], shippingAmount?, note?)
     * Replaces the order's item set with the desired one and returns the
     * recomputed totals + items.
     */
    public function editItems($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireCashier($context->user());

        $validator = Validator::make($args, [
            'orderId'          => 'required|integer',
            'items'            => 'required|array|min:1',
            'items.*.productId' => 'required|integer',
            'items.*.qty'      => 'required|integer|min:1',
            'shippingAmount'   => 'nullable|numeric|min:0',
            'note'             => 'nullable|string|max:1000',
        ]);
        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        $order = $this->resolveScopedOrder((int) $args['orderId'], $context, ['items.product', 'channel', 'payment']);

        // Map the GraphQL camelCase items to the service's product_id/qty shape.
        $items = array_map(fn ($r) => [
            'product_id' => (int) $r['productId'],
            'qty'        => (int) $r['qty'],
        ], $args['items']);

        try {
            $fresh = $this->itemEditor->apply(
                $order,
                $items,
                array_key_exists('shippingAmount', $args) ? $args['shippingAmount'] : null,
                $args['note'] ?? null,
            );
        } catch (OrderEditException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage().($e->errors ? ' '.implode(' ', $e->errors) : ''),
                'order'   => null,
                'items'   => [],
            ];
        }

        return [
            'success' => true,
            'message' => trans('pos::app.outlet.orders.items-updated'),
            'order'   => [
                'subTotal'      => (float) $fresh->sub_total,
                'shippingAmount' => (float) $fresh->shipping_amount,
                'taxAmount'     => (float) $fresh->tax_amount,
                'discountAmount' => (float) $fresh->discount_amount,
                'codFee'        => (float) ($fresh->cod_fee ?? 0),
                'coolSurcharge' => (float) ($fresh->cool_surcharge ?? 0),
                'grandTotal'    => (float) $fresh->grand_total,
            ],
            'items' => $fresh->items->map(fn ($i) => [
                'id'        => (int) $i->id,
                'productId' => (int) $i->product_id,
                'sku'       => $i->sku,
                'name'      => $i->name,
                'qty'       => (int) $i->qty_ordered,
                'price'     => (float) $i->price,
                'total'     => (float) $i->total,
            ])->values()->toArray(),
        ];
    }

    /**
     * searchEditableProducts(orderId, query?, page?, perPage?) — the picker for
     * adding a line. Simple products on the order's channel with stock.
     */
    public function searchProducts($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireCashier($context->user());

        $validator = Validator::make($args, [
            'orderId' => 'required|integer',
            'query'   => 'nullable|string|max:255',
            'page'    => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1|max:100',
        ]);
        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        $order = $this->resolveScopedOrder((int) $args['orderId'], $context);

        $res = $this->itemEditor->searchProducts(
            $order,
            (string) ($args['query'] ?? ''),
            (int) ($args['page'] ?? 1),
            (int) ($args['perPage'] ?? 30),
        );

        return [
            'data' => array_map(fn ($p) => [
                'id'         => (int) $p['id'],
                'sku'        => $p['sku'],
                'name'       => $p['name'],
                'image'      => $p['image'],
                'price'      => (float) $p['price'],
                'qtyInStock' => (int) $p['qty_in_stock'],
            ], $res['data']),
            'page'    => $res['page'],
            'perPage' => $res['per_page'],
            'total'   => $res['total'],
            'hasMore' => $res['has_more'],
        ];
    }

    /**
     * Load an order scoped to the agent's outlet channel (or a POS register
     * sale on their outlet). 404s for any other store's order.
     */
    protected function resolveScopedOrder(int $orderId, GraphQLContext $context, array $with = []): Order
    {
        $user      = $context->user();
        $channelId = $user->outlet?->channel_id;

        $order = $this->orderRepository->with($with)->find($orderId);

        $belongsToChannel = $channelId !== null && (int) $order?->channel_id === (int) $channelId;
        $belongsToOutlet  = $order
            && DB::table('pos_order')
                ->where('order_id', $order->id)
                ->where('outlet_id', $user->outlet_id)
                ->exists();

        if (! $order || (! $belongsToChannel && ! $belongsToOutlet)) {
            throw new CustomException(trans('pos::app.outlet.orders.not-found'));
        }

        return $order;
    }
}
