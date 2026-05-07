<?php

namespace Webkul\Pos\DataGrids\Admin;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\Sales\Models\Order;

class ReportDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('orders')
            ->join('pos_order', 'pos_order.order_id', '=', 'orders.id')
            ->leftJoin('order_payment', 'order_payment.order_id', '=', 'orders.id')
            ->leftJoin('pos_outlets', 'pos_order.outlet_id', '=', 'pos_outlets.id')
            ->select(
                'orders.id',
                'orders.increment_id as order_increment_id',
                'orders.base_sub_total',
                'orders.base_grand_total',
                'orders.created_at',
                'orders.status as order_status',
                'order_payment.method_title',
                'pos_order.order_note',
                'pos_order.bank_name',
                'pos_outlets.id as pos_outlet_id',
                'pos_outlets.name as outlet_name',
            );

        $this->addFilter('order_increment_id', 'orders.increment_id');
        $this->addFilter('outlet_name', 'pos_outlets.name');
        $this->addFilter('order_note', 'pos_order.order_note');
        $this->addFilter('order_status', 'orders.status');
        $this->addFilter('method_title', 'order_payment.method_title');
        $this->addFilter('created_at', 'orders.created_at');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'           => 'created_at',
            'label'           => trans('pos::app.admin.sales-reports.index.datagrid.order-date'),
            'type'            => 'date',
            'filterable'      => true,
            'filterable_type' => 'date_range',
            'sortable'        => true,
        ]);

        $this->addColumn([
            'index'      => 'order_increment_id',
            'label'      => trans('pos::app.admin.sales-reports.index.datagrid.order-id'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => false,
        ]);

        $this->addColumn([
            'index'      => 'outlet_name',
            'label'      => trans('pos::app.admin.sales-reports.index.datagrid.outlet-name'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => false,
        ]);

        $this->addColumn([
            'index'      => 'order_note',
            'label'      => trans('pos::app.admin.sales-reports.index.datagrid.order-note'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'method_title',
            'label'      => trans('pos::app.admin.sales-reports.index.datagrid.payment-method'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'base_grand_total',
            'label'      => trans('pos::app.admin.sales-reports.index.datagrid.grand-total'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'bank_name',
            'label'      => trans('pos::app.admin.sales-reports.index.datagrid.bank-name'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'              => 'order_status',
            'label'              => trans('pos::app.admin.sales-reports.index.datagrid.status.title'),
            'type'               => 'string',
            'filterable_type'    => 'dropdown',
            'filterable_options' => [
                [
                    'label' => trans('pos::app.admin.sales-reports.index.datagrid.status.options.processing'),
                    'value' => Order::STATUS_PROCESSING,
                ],
                [
                    'label' => trans('pos::app.admin.sales-reports.index.datagrid.status.options.completed'),
                    'value' => Order::STATUS_COMPLETED,
                ],
                [
                    'label' => trans('pos::app.admin.sales-reports.index.datagrid.status.options.canceled'),
                    'value' => Order::STATUS_CANCELED,
                ],
                [
                    'label' => trans('pos::app.admin.sales-reports.index.datagrid.status.options.closed'),
                    'value' => Order::STATUS_CLOSED,
                ],
                [
                    'label' => trans('pos::app.admin.sales-reports.index.datagrid.status.options.pending'),
                    'value' => Order::STATUS_PENDING,
                ],
                [
                    'label' => trans('pos::app.admin.sales-reports.index.datagrid.status.options.pending-payment'),
                    'value' => Order::STATUS_PENDING_PAYMENT,
                ],
                [
                    'label' => trans('pos::app.admin.sales-reports.index.datagrid.status.options.fraud'),
                    'value' => Order::STATUS_FRAUD,
                ],
            ],
            'searchable'         => true,
            'filterable'         => true,
            'sortable'           => true,
            'closure'            => function ($row) {
                switch ($row->order_status) {
                    case Order::STATUS_PROCESSING:
                        return '<p class="label-processing">'.trans('pos::app.admin.sales-reports.index.datagrid.status.options.processing').'</p>';

                    case Order::STATUS_COMPLETED:
                        return '<p class="label-active">'.trans('pos::app.admin.sales-reports.index.datagrid.status.options.completed').'</p>';

                    case Order::STATUS_CANCELED:
                        return '<p class="label-canceled">'.trans('pos::app.admin.sales-reports.index.datagrid.status.options.canceled').'</p>';

                    case Order::STATUS_CLOSED:
                        return '<p class="label-closed">'.trans('pos::app.admin.sales-reports.index.datagrid.status.options.closed').'</p>';

                    case Order::STATUS_PENDING:
                        return '<p class="label-pending">'.trans('pos::app.admin.sales-reports.index.datagrid.status.options.pending').'</p>';

                    case Order::STATUS_PENDING_PAYMENT:
                        return '<p class="label-pending">'.trans('pos::app.admin.sales-reports.index.datagrid.status.options.pending-payment').'</p>';

                    case Order::STATUS_FRAUD:
                        return '<p class="label-canceled">'.trans('pos::app.admin.sales-reports.index.datagrid.status.options.fraud').'</p>';
                }
            },
        ]);
    }

    /**
     * Prepare Actions
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('pos.sales_report.view')) {
            $this->addAction([
                'icon'   => 'icon-view',
                'title'  => trans('pos::app.admin.sales-reports.index.datagrid.view'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.sales.orders.view', $row->id);
                },
            ]);
        }
    }
}
