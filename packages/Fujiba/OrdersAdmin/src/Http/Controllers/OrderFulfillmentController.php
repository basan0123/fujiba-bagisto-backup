<?php

namespace Fujiba\OrdersAdmin\Http\Controllers;

use Fujiba\OrdersAdmin\Http\Requests\UpdateOrderFulfillmentRequest;
use Fujiba\OrdersAdmin\Services\FulfillmentRecalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\Sales\Repositories\OrderRepository;

class OrderFulfillmentController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected FulfillmentRecalculator $recalc,
    ) {}

    /**
     * Compute new totals for a given fulfillment type WITHOUT persisting.
     * Used by the modal to render a fee preview before the user confirms.
     */
    public function preview(UpdateOrderFulfillmentRequest $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->findOrFail($id);

        $type = $request->validated('type');
        $next = $this->recalc->compute($order, $type);

        return response()->json([
            'preview' => [
                'type'            => $type,
                'shipping_amount' => $next['shipping_amount'],
                'cod_fee'         => $next['cod_fee'],
                'cool_surcharge'  => $next['cool_surcharge'],
                'grand_total'     => $next['grand_total'],
            ],
            'current' => [
                'fulfillment_type' => $order->fulfillment_type,
                'shipping_amount'  => (float) $order->shipping_amount,
                'cod_fee'          => (float) $order->cod_fee,
                'cool_surcharge'   => (float) ($order->cool_surcharge ?? 0),
                'grand_total'      => (float) $order->grand_total,
            ],
            'needs_cool' => $this->recalc->orderNeedsCoolShipping($order),
        ]);
    }

    public function update(UpdateOrderFulfillmentRequest $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->findOrFail($id);

        $type = $request->validated('type');
        $next = $this->recalc->compute($order, $type);

        $order->update($next);

        return response()->json([
            'message' => 'Fulfillment updated.',
            'data'    => $order->fresh(['items', 'payment', 'customer', 'shipments', 'comments', 'addresses']),
        ]);
    }
}
