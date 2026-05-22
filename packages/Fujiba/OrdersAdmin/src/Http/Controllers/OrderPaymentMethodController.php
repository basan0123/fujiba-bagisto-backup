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

        // Payment method is editable in any status — staff sometimes need to
        // re-classify after the fact (e.g. flip cashondelivery → kpay once
        // the customer pays via the mobile wallet at handover, even if the
        // order has already moved to processing / completed).
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

        // Only cashondelivery (Sagawa) carries the 代引手数料. cash, kpay and
        // moneytransfer all have zero surcharge — they go through the
        // "else" branch above.
        $methodLabel = match ($method) {
            'cashondelivery' => '代金引換',
            'cash'           => '現金',
            'kpay'           => 'KPay',
            'moneytransfer'  => '銀行振込',
            default          => $method,
        };
        $feeNote = match ($method) {
            'cashondelivery' => ' 代引手数料: ¥' . number_format($codFee) . ' 追加。',
            'cash', 'kpay', 'moneytransfer' => ' 代引手数料を除外しました。',
            default          => '',
        };
        $this->orderCommentRepository->create([
            'order_id' => $order->id,
            'comment'  => "支払方法を「{$methodLabel}」に変更しました。" .
                $feeNote .
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
