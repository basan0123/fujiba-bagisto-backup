<?php

namespace Fujiba\InvoicesAdmin\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\Admin\DataGrids\Sales\OrderInvoiceDataGrid;

/**
 * Adds shipping-status + payment-method columns and matching filters to the
 * core /admin/sales/invoices grid (closes the original spec section 5.B).
 *
 * Default sort: pending invoices first, then pending_payment, then paid /
 * overdue. Operators wanted actionable rows at the top.
 */
class FujibaInvoiceDataGrid extends OrderInvoiceDataGrid
{
    public function prepareQueryBuilder()
    {
        $qb = parent::prepareQueryBuilder();

        $qb->leftJoin('order_payment', 'order_payment.order_id', '=', 'orders.id')
           ->addSelect(
               'order_payment.method as payment_method',
               'orders.fujiba_label_printed as fujiba_label_printed',
           );

        $this->addFilter('payment_method', 'order_payment.method');
        $this->addFilter('order_status', 'orders.status');
        $this->addFilter('fujiba_label_printed', 'orders.fujiba_label_printed');

        // Default sort — actionable invoices at the top. Anything not listed
        // (e.g. canceled) sinks to the bottom via the ELSE 9 fallthrough.
        $qb->orderByRaw("CASE
            WHEN invoices.state = 'pending'         THEN 1
            WHEN invoices.state = 'pending_payment' THEN 2
            WHEN invoices.state = 'overdue'         THEN 3
            WHEN invoices.state = 'paid'            THEN 4
            ELSE 9
        END");

        return $qb;
    }

    public function prepareColumns()
    {
        parent::prepareColumns();

        $this->addColumn([
            'index'      => 'shipping_status',
            'label'      => 'Shipping',
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => false,
            'closure'    => function ($row) {
                if ((int) ($row->fujiba_label_printed ?? 0) === 1) {
                    return $row->order_status === 'completed' ? '配達済' : '発送済';
                }
                return match ($row->order_status) {
                    'pending', 'pending_payment' => '未発送',
                    'completed'                  => '配達済',
                    'canceled'                   => 'キャンセル',
                    default                      => $row->order_status,
                };
            },
        ]);

        $this->addColumn([
            'index'      => 'payment_method',
            'label'      => 'Payment Method',
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                return match ($row->payment_method ?? '') {
                    'cashondelivery' => '代金引換',
                    'moneytransfer'  => '銀行振込',
                    ''               => '—',
                    default          => $row->payment_method,
                };
            },
        ]);
    }
}
