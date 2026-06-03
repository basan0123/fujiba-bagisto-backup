<?php

namespace Fujiba\OrdersAdmin\Http\Controllers;

use Fujiba\OrdersAdmin\Http\Requests\UpdateOrderPaymentConfirmRequest;
use Fujiba\OrdersAdmin\Services\OrderPaymentConfirmer;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\Sales\Repositories\OrderRepository;

class OrderPaymentConfirmController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected OrderPaymentConfirmer $paymentConfirmer,
    ) {}

    public function update(UpdateOrderPaymentConfirmRequest $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->findOrFail($id);

        $confirmed = (bool) $request->validated('confirmed');

        $data = $this->paymentConfirmer->confirm($order, $confirmed, $request->validated('note'));

        return response()->json([
            'message' => $confirmed
                ? trans('OrdersAdmin::app.payment-confirm.confirmed')
                : trans('OrdersAdmin::app.payment-confirm.unconfirmed'),
            'data' => $data,
        ]);
    }
}
