<?php

namespace Fujiba\OrdersAdmin\Http\Controllers;

use Fujiba\OrdersAdmin\Services\OrderLabelPrinter;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\Sales\Repositories\OrderRepository;

class OrderLabelPrintedController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected OrderLabelPrinter $labelPrinter,
    ) {}

    public function flip(int $id): JsonResponse
    {
        if (! bouncer()->hasPermission('sales.orders.edit')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $order = $this->orderRepository->findOrFail($id);

        $result = $this->labelPrinter->flip($order);

        return response()->json([
            'message' => $result['already']
                ? trans('OrdersAdmin::app.label-printed.already')
                : trans('OrdersAdmin::app.label-printed.flipped'),
            'data' => ['label_printed' => $result['label_printed']],
        ]);
    }
}
