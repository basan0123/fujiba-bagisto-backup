<?php

namespace Webkul\Pos\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Pos\Contracts\CustomerCredit;

class CustomerCreditRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return CustomerCredit::class;
    }

    /**
     * Create customer credit.
     */
    public function create(array $data)
    {
        [$order, $args] = [$data['order'], $data['args']];

        $credit = [
            'order_id'     => $order->id,
            'customer_id'  => $order->customer_id,
            'payment_mode' => $args['payment_mode'],
            'used_status'  => false,
        ];

        if ($args['payment_mode'] == 'pos_cash') {
            $changeAmount = $args['cash_total'] - $order->grand_total;

            $credit = array_merge($credit, [
                'tendered_amount'      => round($args['cash_total'], 2),
                'base_tendered_amount' => round(core()->convertToBasePrice($args['cash_total'], $order->order_currency_code), 2),
                'change_amount'        => round($changeAmount, 2),
                'base_change_amount'   => round(core()->convertToBasePrice($changeAmount, $order->order_currency_code), 2),
            ]);
        }

        if (
            $args['payment_mode'] == 'pos_card'
            || $args['payment_mode'] == 'pos_split'
        ) {
            $credit = array_merge($credit, [
                'tendered_amount'      => round($order->grand_total, 2),
                'base_tendered_amount' => round($order->base_grand_total, 2),
                'change_amount'        => 0,
                'base_change_amount'   => 0,
            ]);
        }

        return $this->model->create($credit);
    }
}
