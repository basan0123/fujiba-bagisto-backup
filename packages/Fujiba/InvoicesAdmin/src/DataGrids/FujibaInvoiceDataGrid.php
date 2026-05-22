<?php

namespace Fujiba\InvoicesAdmin\DataGrids;

use Webkul\Admin\DataGrids\Sales\OrderInvoiceDataGrid;

/**
 * Adds shipping-status + payment-method columns and matching filters to the
 * core /admin/sales/invoices grid.
 *
 * Canonical "shipped" predicate (mirrors the advanced screen at
 * /admin/sales/orders?view=listview, the only screen K confirms is correct):
 *
 *   orders.fujiba_label_printed = 1  ⇒  shipped (green badge)
 *   orders.fujiba_label_printed = 0  ⇒  not yet shipped (yellow badge)
 *   orders.status = 'canceled'       ⇒  canceled (red badge)
 *
 * Reading orders.status alone is NOT enough — the bug class (e.g. #1558,
 * #1546, #1544 on 2026-05-11) is a fully-shipped order whose status stayed
 * at 'pending_payment' / 'processing' because OrderLabelPrintedController
 * doesn't advance status. The label_printed flag is the single source of
 * truth for "physically out the door."
 *
 * The filter "shipping_status" lets accounts cross-check that mismatch:
 * pick "shipped" to see all label-printed rows regardless of orders.status,
 * including ones with mismatched legacy status values.
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
               'orders.shipping_method as shipping_method',
               // exposed for the row-level Receipt action below; the parent
               // grid only selects orders.increment_id (string), not the PK.
               'orders.id as order_pk',
           );

        $this->addFilter('payment_method', 'order_payment.method');
        $this->addFilter('order_status', 'orders.status');
        // The cross-check filter K asked for: pick "1" to see all
        // label-printed rows (the canonical "shipped" predicate),
        // independent of orders.status.
        $this->addFilter('shipping_status', 'orders.fujiba_label_printed');

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
            'label'      => '発送',
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => false,
            'closure'    => function ($row) {
                return self::renderShippingBadge(
                    (int) ($row->fujiba_label_printed ?? 0),
                    (string) ($row->order_status ?? ''),
                    (string) ($row->shipping_method ?? '')
                );
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
                    'cash'           => '現金',
                    ''               => '—',
                    default          => $row->payment_method,
                };
            },
        ]);

        // Reorder: invoice id → customer → payment method → shipping status →
        // everything else, with order_id pushed to the end. The two id columns
        // (invoice + order) were eating too much horizontal space at the front;
        // operators only need order_id to drill into the source order, not to
        // scan the list.
        $this->reorderColumns(
            front: [
                'increment_id',
                'customer_full_name',
                'payment_method',
                'shipping_status',
            ],
            back: [
                'order_id',
            ],
        );
    }

    /**
     * Move named columns to the front and/or back of the grid in the given
     * order; everything else keeps its relative order in the middle.
     * Tolerates missing names by skipping them silently.
     */
    protected function reorderColumns(array $front = [], array $back = []): void
    {
        $byIndex = [];
        foreach ($this->getColumns() as $col) {
            $byIndex[$col->getIndex()] = $col;
        }

        $head = [];
        foreach ($front as $name) {
            if (isset($byIndex[$name])) {
                $head[] = $byIndex[$name];
                unset($byIndex[$name]);
            }
        }

        $tail = [];
        foreach ($back as $name) {
            if (isset($byIndex[$name])) {
                $tail[] = $byIndex[$name];
                unset($byIndex[$name]);
            }
        }

        $this->setColumns(array_merge($head, array_values($byIndex), $tail));
    }

    public function prepareActions()
    {
        parent::prepareActions();

        // Receipt = the mobile-friendly print view operators hand to packers.
        // Linking from invoices keeps them from bouncing through Orders just
        // to print/share the picking slip.
        $this->addAction([
            'icon'   => 'icon-printer',
            'title'  => 'レシート / Receipt',
            'method' => 'GET',
            'url'    => function ($row) {
                return route('admin.sales.orders.receipt', $row->order_pk);
            },
        ]);
    }

    /**
     * Single shipping-status badge renderer — shared with FujibaOrderDataGrid.
     * Green for shipped/completed, yellow for not-yet-shipped, red for canceled.
     * Public + static so other grids (datagrid view of orders) reuse the same
     * predicate. Don't duplicate this logic anywhere else — that's how the
     * #1558-class bug sneaks back in.
     */
    public static function renderShippingBadge(int $labelPrinted, string $orderStatus, string $shippingMethod = ''): string
    {
        if ($orderStatus === 'canceled') {
            return '<span style="display:inline-block;padding:2px 10px;border-radius:9999px;background:#fee2e2;color:#991b1b;font-size:12px;font-weight:600;">キャンセル</span>';
        }
        // Store Pickup orders have nothing to ship — they're handed over at the
        // store (typically POS/cash). They must never display "未発送".
        if ($shippingMethod === 'free_free') {
            return '<span style="display:inline-block;padding:2px 10px;border-radius:9999px;background:#d1fae5;color:#065f46;font-size:12px;font-weight:600;">受取済</span>';
        }
        if ($labelPrinted === 1) {
            $label = $orderStatus === 'completed' ? '配達済' : '発送済';
            return '<span style="display:inline-block;padding:2px 10px;border-radius:9999px;background:#d1fae5;color:#065f46;font-size:12px;font-weight:600;">'.$label.'</span>';
        }
        return '<span style="display:inline-block;padding:2px 10px;border-radius:9999px;background:#fef3c7;color:#92400e;font-size:12px;font-weight:600;">未発送</span>';
    }
}
