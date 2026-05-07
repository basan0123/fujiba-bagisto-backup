<?php

namespace Webkul\Pos\DataGrids\Admin;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\Sales\Models\Order;

class OrderDataGrid extends DataGrid
{
    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'order_id';

    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('pos_order')
            ->leftJoin('orders', 'pos_order.order_id', '=', 'orders.id')
            ->select(
                'orders.status',
                'pos_order.order_id',
                'pos_order.order_id as order_id',
                'pos_order.order_ref_id as order_ref_id',
                'orders.base_grand_total as base_grand_total',
                'orders.created_at as created_at'
            )
            ->addSelect(DB::raw('CONCAT('.DB::getTablePrefix().'orders.customer_first_name, " ", '.DB::getTablePrefix().'orders.customer_last_name) AS full_name'))
            ->groupBy();

        $this->addFilter('order_id', 'pos_order.order_id');
        $this->addFilter('order_ref_id', 'pos_order.order_ref_id');
        $this->addFilter('full_name', DB::raw('CONCAT('.DB::getTablePrefix().'orders.customer_first_name, " ", orders.customer_last_name)'));
        $this->addFilter('created_at', 'orders.created_at');
        $this->addFilter('base_grand_total', 'orders.base_grand_total');
        $this->addFilter('status', 'orders.status');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'order_id',
            'label'      => trans('pos::app.admin.orders.index.datagrid.order-id'),
            'type'       => 'integer',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'order_ref_id',
            'label'      => trans('pos::app.admin.orders.index.datagrid.order-ref-id'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'full_name',
            'label'      => trans('pos::app.admin.orders.index.datagrid.customer-name'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'           => 'created_at',
            'label'           => trans('pos::app.admin.orders.index.datagrid.order-date'),
            'type'            => 'date',
            'filterable'      => true,
            'filterable_type' => 'date_range',
            'sortable'        => true,
        ]);

        $this->addColumn([
            'index'      => 'base_grand_total',
            'label'      => trans('pos::app.admin.orders.index.datagrid.grand-total'),
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'              => 'status',
            'label'              => trans('pos::app.admin.orders.index.datagrid.status.title'),
            'type'               => 'string',
            'filterable_type'    => 'dropdown',
            'filterable_options' => [
                [
                    'label' => trans('pos::app.admin.orders.index.datagrid.status.options.processing'),
                    'value' => Order::STATUS_PROCESSING,
                ],
                [
                    'label' => trans('pos::app.admin.orders.index.datagrid.status.options.completed'),
                    'value' => Order::STATUS_COMPLETED,
                ],
                [
                    'label' => trans('pos::app.admin.orders.index.datagrid.status.options.canceled'),
                    'value' => Order::STATUS_CANCELED,
                ],
                [
                    'label' => trans('pos::app.admin.orders.index.datagrid.status.options.closed'),
                    'value' => Order::STATUS_CLOSED,
                ],
                [
                    'label' => trans('pos::app.admin.orders.index.datagrid.status.options.pending'),
                    'value' => Order::STATUS_PENDING,
                ],
                [
                    'label' => trans('pos::app.admin.orders.index.datagrid.status.options.pending-payment'),
                    'value' => Order::STATUS_PENDING_PAYMENT,
                ],
                [
                    'label' => trans('pos::app.admin.orders.index.datagrid.status.options.fraud'),
                    'value' => Order::STATUS_FRAUD,
                ],
            ],
            'searchable'         => true,
            'filterable'         => true,
            'sortable'           => true,
            'closure'            => function ($row) {
                switch ($row->status) {
                    case Order::STATUS_PROCESSING:
                        return '<p class="label-processing">'.trans('pos::app.admin.orders.index.datagrid.status.options.processing').'</p>';

                    case Order::STATUS_COMPLETED:
                        return '<p class="label-active">'.trans('pos::app.admin.orders.index.datagrid.status.options.completed').'</p>';

                    case Order::STATUS_CANCELED:
                        return '<p class="label-canceled">'.trans('pos::app.admin.orders.index.datagrid.status.options.canceled').'</p>';

                    case Order::STATUS_CLOSED:
                        return '<p class="label-closed">'.trans('pos::app.admin.orders.index.datagrid.status.options.closed').'</p>';

                    case Order::STATUS_PENDING:
                        return '<p class="label-pending">'.trans('pos::app.admin.orders.index.datagrid.status.options.pending').'</p>';

                    case Order::STATUS_PENDING_PAYMENT:
                        return '<p class="label-pending">'.trans('pos::app.admin.orders.index.datagrid.status.options.pending-payment').'</p>';

                    case Order::STATUS_FRAUD:
                        return '<p class="label-canceled">'.trans('pos::app.admin.orders.index.datagrid.status.options.fraud').'</p>';
                }
            },
        ]);
    }

    /**
     * Prepare Actions
     */
    public function prepareActions(): void
    {
        $this->addAction([
            'icon'   => 'icon-view',
            'title'  => trans('pos::app.admin.orders.index.datagrid.view'),
            'method' => 'GET',
            'url'    => function ($row) {
                return route('admin.sales.orders.view', $row->order_id);
            },
        ]);
    }
}
