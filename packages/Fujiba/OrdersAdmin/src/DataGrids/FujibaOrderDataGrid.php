<?php

namespace Fujiba\OrdersAdmin\DataGrids;

use Fujiba\InvoicesAdmin\DataGrids\FujibaInvoiceDataGrid;
use Webkul\Admin\DataGrids\Sales\OrderDataGrid;

/**
 * Adds the shipping-status badge column + cross-check filter to the core
 * /admin/sales/orders?view=datagrid grid.
 *
 * Why we need this:
 *   The core grid renders orders.status only. That's NOT the canonical
 *   "shipped" predicate on Fujiba — orders.status often lags
 *   fujiba_label_printed (the OrderLabelPrintedController bug class:
 *   #1558, #1546, #1544 on 2026-05-11 all had label_printed=1 but
 *   status still at pending_payment/processing). The advanced screen at
 *   ?view=listview routes through OrderListController which DOES honor
 *   fujiba_label_printed — that's why K confirms it as correct.
 *
 * Single source of truth:
 *   The badge rendering delegates to FujibaInvoiceDataGrid::renderShippingBadge.
 *   Same predicate, same colors, same labels, both screens. If anyone
 *   needs to change the rule, change it there and both grids update.
 */
class FujibaOrderDataGrid extends OrderDataGrid
{
    public function prepareQueryBuilder()
    {
        $qb = parent::prepareQueryBuilder();

        // Bring fujiba_label_printed into the projection so the column
        // closure can read it. Schema check is implicit — the column has
        // existed on production since 2026-04-28, see
        // packages/Fujiba/OrdersAdmin/src/Database/Migrations/
        // 2026_04_28_000002_add_fujiba_label_printed_to_orders_table.php.
        $qb->addSelect('orders.fujiba_label_printed as fujiba_label_printed');

        // Cross-check filter: shipping_status=1 selects every row with
        // label_printed=1, independent of orders.status. Surfaces the
        // #1558-class mismatch (shipped order whose status didn't advance).
        $this->addFilter('shipping_status', 'orders.fujiba_label_printed');

        return $qb;
    }

    public function prepareColumns()
    {
        parent::prepareColumns();

        $this->addColumn([
            'index'      => 'shipping_status',
            'label'      => '発送',
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => false,
            'closure'    => function ($row) {
                return FujibaInvoiceDataGrid::renderShippingBadge(
                    (int) ($row->fujiba_label_printed ?? 0),
                    (string) ($row->status ?? '')
                );
            },
        ]);
    }
}
