<?php

namespace Fujiba\OrdersAdmin\Http\Controllers;

use Fujiba\OrdersAdmin\Http\Requests\UpdateOrderStatusRequest;
use Fujiba\OrdersAdmin\Services\OrderStatusUpdater;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\Sales\Repositories\OrderRepository;

class OrderStatusController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected OrderStatusUpdater $statusUpdater,
    ) {}

    public function update(UpdateOrderStatusRequest $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->findOrFail($id);

        $this->statusUpdater->update(
            $order,
            $request->validated('status'),
            $request->validated('customer_message'),
        );

        return response()->json([
            'message' => 'Status updated.',
            'data'    => $order->fresh(['comments']),
        ]);
    }
}
