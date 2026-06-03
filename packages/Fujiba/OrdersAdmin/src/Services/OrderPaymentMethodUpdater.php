<?php

namespace Fujiba\OrdersAdmin\Services;

use Fujiba\OrdersAdmin\Exceptions\OrderEditException;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderCommentRepository;

/**
 * Changes an order's payment method, applying/removing the cashondelivery
 * (Sagawa) 代引手数料 and writing a Japanese audit comment. Shared by the
 * admin REST controller and the POS GraphQL mutation.
 *
 * Editable in ANY status — staff sometimes re-classify after the fact (e.g.
 * cashondelivery → kpay once the customer pays via wallet at handover, even
 * if the order already moved to processing/completed).
 */
class OrderPaymentMethodUpdater
{
    public function __construct(
        protected OrderCommentRepository $orderCommentRepository,
    ) {}

    /**
     * @return array{cod_fee_applied:float,new_grand_total:float}
     *
     * @throws OrderEditException
     */
    public function update(Order $order, string $method): array
    {
        $payment = $order->payment;

        if (! $payment) {
            throw new OrderEditException('Order has no payment record.');
        }

        if ($payment->method === $method) {
            throw new OrderEditException('Order is already on that payment method.');
        }

        $baseAmount  = (float) $order->base_grand_total - (float) $order->base_cod_fee;
        $codFeeUnder = (float) (core()->getConfigData('sales.payment_methods.cashondelivery.cod_fee_under_10000') ?: 330);
        $codFeeAbove = (float) (core()->getConfigData('sales.payment_methods.cashondelivery.cod_fee_10000_and_above') ?: 440);
        $codFee      = $baseAmount < 10000 ? $codFeeUnder : $codFeeAbove;
        $oldTotal    = (float) $order->base_grand_total;

        if ($method === 'cashondelivery') {
            $order->cod_fee          = $codFee;
            $order->base_cod_fee     = $codFee;
            $order->grand_total      = $baseAmount + $codFee;
            $order->base_grand_total = $baseAmount + $codFee;
        } else {
            $order->cod_fee          = 0;
            $order->base_cod_fee     = 0;
            $order->grand_total      = $baseAmount;
            $order->base_grand_total = $baseAmount;
        }

        $order->save();

        $payment->update([
            'method'       => $method,
            'method_title' => core()->getConfigData('sales.payment_methods.'.$method.'.title') ?: $method,
        ]);

        // Only cashondelivery (Sagawa) carries the 代引手数料. cash, kpay and
        // moneytransfer all have zero surcharge.
        $methodLabel = match ($method) {
            'cashondelivery' => '代金引換',
            'cash'           => '現金',
            'kpay'           => 'KPay',
            'moneytransfer'  => '銀行振込',
            default          => $method,
        };
        $feeNote = match ($method) {
            'cashondelivery' => ' 代引手数料: ¥'.number_format($codFee).' 追加。',
            'cash', 'kpay', 'moneytransfer' => ' 代引手数料を除外しました。',
            default          => '',
        };
        $this->orderCommentRepository->create([
            'order_id' => $order->id,
            'comment'  => "支払方法を「{$methodLabel}」に変更しました。".
                $feeNote.
                ' 合計: ¥'.number_format((float) $order->base_grand_total).
                ' (旧合計: ¥'.number_format($oldTotal).')',
        ]);

        return [
            'cod_fee_applied' => (float) $order->base_cod_fee,
            'new_grand_total' => (float) $order->base_grand_total,
        ];
    }
}
