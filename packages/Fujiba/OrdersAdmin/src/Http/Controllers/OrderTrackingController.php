<?php

namespace Fujiba\OrdersAdmin\Http\Controllers;

use Fujiba\OrdersAdmin\Exceptions\OrderEditException;
use Fujiba\OrdersAdmin\Http\Requests\UpdateOrderTrackingRequest;
use Fujiba\OrdersAdmin\Services\OrderTrackingUpdater;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\Sales\Repositories\OrderRepository;

class OrderTrackingController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected OrderTrackingUpdater $trackingUpdater,
    ) {}

    public function update(UpdateOrderTrackingRequest $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->findOrFail($id);

        try {
            $shipment = $this->trackingUpdater->update($order, $request->validated('tracking_number'));
        } catch (OrderEditException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => trans('OrdersAdmin::app.tracking.updated'),
            'data'    => $shipment,
        ]);
    }
}
