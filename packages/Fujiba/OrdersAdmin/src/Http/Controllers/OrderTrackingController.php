<?php

namespace Fujiba\OrdersAdmin\Http\Controllers;

use Fujiba\OrdersAdmin\Http\Requests\UpdateOrderTrackingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\Sales\Repositories\OrderRepository;

class OrderTrackingController extends Controller
{
    public function __construct(protected OrderRepository $orderRepository) {}

    public function update(UpdateOrderTrackingRequest $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->findOrFail($id);

        $shipment = $order->shipments()->latest('id')->first();

        if (! $shipment) {
            return response()->json([
                'message' => 'No shipment to update. Create a shipment first.',
            ], 422);
        }

        $shipment->update([
            'track_number' => $request->validated('tracking_number'),
        ]);

        return response()->json([
            'message' => 'Tracking updated.',
            'data'    => $shipment->fresh(),
        ]);
    }
}
