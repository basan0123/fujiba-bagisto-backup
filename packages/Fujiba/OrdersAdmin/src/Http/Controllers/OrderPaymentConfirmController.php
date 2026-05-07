<?php

namespace Fujiba\OrdersAdmin\Http\Controllers;

use Fujiba\OrdersAdmin\Http\Requests\UpdateOrderPaymentConfirmRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Webkul\Sales\Repositories\OrderRepository;

class OrderPaymentConfirmController extends Controller
{
    public function __construct(protected OrderRepository $orderRepository) {}

    public function update(UpdateOrderPaymentConfirmRequest $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->findOrFail($id);

        $confirmed = (bool) $request->validated('confirmed');
        $note      = $request->validated('note');

        $order->update([
            'payment_confirmed_at' => $confirmed ? Carbon::now() : null,
            'payment_note'         => $note,
        ]);

        $fresh = $order->fresh();

        return response()->json([
            'message' => $confirmed ? 'Payment confirmed.' : 'Payment unconfirmed.',
            'data'    => [
                'payment_confirmed_at' => $fresh->payment_confirmed_at
                    ? Carbon::parse($fresh->payment_confirmed_at)->toIso8601String()
                    : null,
                'payment_note'         => $fresh->payment_note,
            ],
        ]);
    }
}
