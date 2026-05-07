<?php

namespace Webkul\Pos\Mutations\Outlet;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\GraphQLAPI\Validators\CustomException;
use Webkul\Pos\Repositories\OrderRepository;
use Webkul\Sales\Repositories\RefundRepository;

class ReturnMutation
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected RefundRepository $refundRepository
    ) {}

    /**
     * Return order
     *
     * @return array
     *
     * @throws CustomException
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context)
    {
        $outletOrder = $this->orderRepository->findOneByField('order_id', $args['order_id']);

        $order = $outletOrder?->order;

        if (! $order?->canRefund()) {
            throw new CustomException(trans('pos::app.outlet.return.creation-error'));
        }

        $data = [
            'refund' => [
                'shipping'          => 0,
                'adjustment_refund' => 0,
                'adjustment_fee'    => 0,
                'items'             => [],
            ],
        ];

        foreach ($args['items'] as $item) {
            $data['refund']['items'][$item['item_id']] = $item['qty'];
        }

        $totals = $this->refundRepository->getOrderItemsRefundSummary(
            $data['refund'],
            $order->id,
        );

        if (! $totals) {
            throw new CustomException(trans('pos::app.outlet.return.invalid-qty'));
        }

        $maxRefundAmount = $totals['grand_total']['price'] - $order->refunds()->sum('base_adjustment_refund');

        $refundAmount = $totals['grand_total']['price'] - $totals['shipping']['price'] + $data['refund']['shipping'] + $data['refund']['adjustment_refund'] - $data['refund']['adjustment_fee'];

        if (! $refundAmount) {
            throw new CustomException(trans('pos::app.outlet.return.invalid-refund-amount-error'));
        }

        if ($refundAmount > $maxRefundAmount) {
            throw new CustomException(trans('pos::app.outlet.return.refund-limit-error', [
                'amount' => core()->formatBasePrice($maxRefundAmount),
            ]));
        }

        $this->refundRepository->create(array_merge($data, ['order_id' => $order->id]));

        return [
            'success'      => true,
            'message'      => trans('pos::app.outlet.return.create-success'),
            'outlet_order' => $outletOrder->refresh(),
        ];
    }
}
