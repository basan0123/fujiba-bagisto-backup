<?php

namespace Fujiba\OrdersAdmin\Http\Controllers;

use Fujiba\OrdersAdmin\Http\Requests\UpdateOrderPaymentConfirmRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Webkul\Sales\Repositories\OrderRepository;

class OrderPaymentConfirmController extends Controller
{
    public function __construct(protected OrderRepository $orderRepository) {}

    public function update(UpdateOrderPaymentConfirmRequest $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->findOrFail($id);

        $confirmed = (bool) $request->validated('confirmed');
        $note      = $request->validated('note');
        $now       = $confirmed ? Carbon::now() : null;

        $payload = [
            'payment_confirmed_at' => $now,
            'payment_note'         => $note,
        ];

        // Mirror onto paid_at for dashboard Payments mode. On confirm, only
        // stamp when paid_at is empty so we don't clobber a ChatPion-sourced
        // value. On unconfirm, clear paid_at only if it matches the
        // confirmation we're reverting (i.e. it was set by us, not by sync).
        if (Schema::hasColumn('orders', 'paid_at')) {
            if ($confirmed) {
                if (! $order->paid_at) {
                    $payload['paid_at'] = $now;
                }
            } elseif ($order->paid_at && $order->payment_confirmed_at
                && (string) $order->paid_at === (string) $order->payment_confirmed_at
            ) {
                $payload['paid_at'] = null;
            }
        }

        $order->update($payload);

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
