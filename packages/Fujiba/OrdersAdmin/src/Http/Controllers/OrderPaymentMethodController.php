<?php

namespace Fujiba\OrdersAdmin\Http\Controllers;

use Fujiba\OrdersAdmin\Http\Requests\UpdateOrderPaymentMethodRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderCommentRepository;
use Webkul\Sales\Repositories\OrderRepository;

class OrderPaymentMethodController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected OrderCommentRepository $orderCommentRepository,
    ) {}

    public function update(UpdateOrderPaymentMethodRequest $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->findOrFail($id);

        if ($order->status !== Order::STATUS_PENDING) {
            return response()->json([
                'message' => 'Payment method can only be changed while the order is pending.',
            ], 422);
        }

        $payment = $order->payment;

        if (! $payment) {
            return response()->json(['message' => 'Order has no payment record.'], 422);
        }

        $method  = $request->validated('method');
        $current = $payment->method;

        if ($current === $method) {
            return response()->json([
                'message' => 'Order is already on that payment method.',
            ], 422);
        }

        $baseAmount  = (float) $order->base_grand_total - (float) $order->base_cod_fee;
        $codFeeUnder = (float) (core()->getConfigData('sales.payment_methods.cashondelivery.cod_fee_under_10000') ?: 330);
        $codFeeAbove = (float) (core()->getConfigData('sales.payment_methods.cashondelivery.cod_fee_10000_and_above') ?: 440);
        $codFee      = $baseAmount < 10000 ? $codFeeUnder : $codFeeAbove;
        $oldTotal    = (float) $order->base_grand_total;

        if ($method === 'cashondelivery') {
            $order->cod_fee         = $codFee;
            $order->base_cod_fee    = $codFee;
            $order->grand_total     = $baseAmount + $codFee;
            $order->base_grand_total = $baseAmount + $codFee;
        } else {
            $order->cod_fee         = 0;
            $order->base_cod_fee    = 0;
            $order->grand_total     = $baseAmount;
            $order->base_grand_total = $baseAmount;
        }

        $order->save();

        $payment->update([
            'method'       => $method,
            'method_title' => core()->getConfigData('sales.payment_methods.' . $method . '.title') ?: $method,
        ]);

        $methodLabel = $method === 'cashondelivery' ? '代金引換' : '銀行振込';
        $this->orderCommentRepository->create([
            'order_id' => $order->id,
            'comment'  => "支払方法を「{$methodLabel}」に変更しました。" .
                ($method === 'cashondelivery'
                    ? ' 代引手数料: ¥' . number_format($codFee) . ' 追加。'
                    : ' 代引手数料を除外しました。') .
                ' 合計: ¥' . number_format((float) $order->base_grand_total) .
                ' (旧合計: ¥' . number_format($oldTotal) . ')',
        ]);

        return response()->json([
            'message'         => 'Payment method updated.',
            'cod_fee_applied' => (float) $order->base_cod_fee,
            'new_grand_total' => (float) $order->base_grand_total,
            'data'            => $order->fresh(['payment']),
        ]);
    }
}
