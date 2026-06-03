<?php

namespace Fujiba\OrdersAdmin\Http\Controllers;

use Fujiba\OrdersAdmin\Exceptions\OrderEditException;
use Fujiba\OrdersAdmin\Http\Requests\UpdateOrderPaymentMethodRequest;
use Fujiba\OrdersAdmin\Services\OrderPaymentMethodUpdater;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\Sales\Repositories\OrderRepository;

class OrderPaymentMethodController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected OrderPaymentMethodUpdater $paymentMethodUpdater,
    ) {}

    public function update(UpdateOrderPaymentMethodRequest $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->findOrFail($id);

        try {
            $result = $this->paymentMethodUpdater->update($order, $request->validated('method'));
        } catch (OrderEditException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message'         => 'Payment method updated.',
            'cod_fee_applied' => $result['cod_fee_applied'],
            'new_grand_total' => $result['new_grand_total'],
            'data'            => $order->fresh(['payment']),
        ]);
    }
}
