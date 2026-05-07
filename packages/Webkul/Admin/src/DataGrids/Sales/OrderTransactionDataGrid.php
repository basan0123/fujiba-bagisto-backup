<?php

namespace Webkul\Admin\DataGrids\Sales;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\DataGrid\DataGrid;

class OrderTransactionDataGrid extends DataGrid
{
    /**
     * Transaction status Paid.
     */
    const STATUS_PAID = 'paid';

    /**
     * Transaction status Pending.
     */
    const STATUS_PENDING = 'pending';

    /**
     * Transaction status Completed
     */
    const STATUS_COMPLETED = 'COMPLETED';

    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $tablePrefix = DB::getTablePrefix();

        $queryBuilder = DB::table('order_transactions')
            ->leftJoin('orders', 'order_transactions.order_id', '=', 'orders.id')
            ->leftJoin('customers', 'orders.customer_id', '=', 'customers.id')
            ->select(
                'order_transactions.id as id',
                'order_transactions.transaction_id as transaction_id',
                'order_transactions.invoice_id as invoice_id',
                'orders.increment_id as order_id',
                'order_transactions.created_at as created_at',
                'order_transactions.amount as amount',
                'order_transactions.status as status',
                'orders.channel_id as channel_id',
                'orders.channel_name as channel_name',
                'customers.image as customer_image'
            )
            ->addSelect(DB::raw('CONCAT(COALESCE('.$tablePrefix.'orders.customer_first_name, ""), " ", COALESCE('.$tablePrefix.'orders.customer_last_name, "")) as customer_full_name'));

        $this->addFilter('id', 'order_transactions.id');
        $this->addFilter('transaction_id', 'order_transactions.transaction_id');
        $this->addFilter('invoice_id', 'order_transactions.invoice_id');
        $this->addFilter('order_id', 'orders.increment_id');
        $this->addFilter('created_at', 'order_transactions.created_at');
        $this->addFilter('status', 'order_transactions.status');
        $this->addFilter('channel_id', 'orders.channel_id');
        $this->addFilter('customer_full_name', DB::raw('CONCAT(COALESCE('.$tablePrefix.'orders.customer_first_name, ""), " ", COALESCE('.$tablePrefix.'orders.customer_last_name, ""))'));

        return $queryBuilder;
    }

    /**
     * Add Columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index' => 'id',
            'label' => trans('admin::app.sales.transactions.index.datagrid.id'),
            'type' => 'integer',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'transaction_id',
            'label' => trans('admin::app.sales.transactions.index.datagrid.transaction-id'),
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'amount',
            'label' => trans('admin::app.sales.transactions.index.datagrid.transaction-amount'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'invoice_id',
            'label' => trans('admin::app.sales.transactions.index.datagrid.invoice-id'),
            'type' => 'integer',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'order_id',
            'label' => trans('admin::app.sales.transactions.index.datagrid.order-id'),
            'type' => 'integer',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'customer_full_name',
            'label' => 'Customer',
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                $name = trim($row->customer_full_name) ?: '—';
                $imgUrl = $row->customer_image ? Storage::url($row->customer_image) : null;
                $initial = strtoupper(mb_substr($name, 0, 1));
                $avatar = $imgUrl
                    ? '<img src="'.e($imgUrl).'" class="h-7 w-7 rounded-full object-cover" alt="">'
                    : '<span class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-400 text-xs font-semibold text-white">'.e($initial).'</span>';

                return '<div class="flex items-center gap-2">'.$avatar.'<span>'.e($name).'</span></div>';
            },
        ]);

        $this->addColumn([
            'index' => 'channel_id',
            'label' => trans('admin::app.sales.orders.index.datagrid.channel-name'),
            'type' => 'string',
            'filterable' => true,
            'filterable_type' => 'dropdown',
            'filterable_options' => core()->getAllChannels()
                ->map(fn ($channel) => ['label' => $channel->name, 'value' => $channel->id])
                ->values()
                ->toArray(),
            'closure' => function ($row) {
                return $row->channel_name ?: '—';
            },
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => trans('admin::app.sales.transactions.index.datagrid.status'),
            'type' => 'string',
            'filterable' => true,
            'filterable_type' => 'dropdown',
            'filterable_options' => [
                [
                    'label' => trans('admin::app.sales.transactions.index.datagrid.paid'),
                    'value' => self::STATUS_PAID,
                ],
                [
                    'label' => trans('admin::app.sales.transactions.index.datagrid.pending'),
                    'value' => self::STATUS_PENDING,
                ],
                [
                    'label' => trans('admin::app.sales.transactions.index.datagrid.completed'),
                    'value' => self::STATUS_COMPLETED,
                ],
            ],
            'sortable' => true,
            'closure' => function ($row) {
                switch ($row->status) {
                    case self::STATUS_PAID:
                        return '<p class="label-active">'.trans('admin::app.sales.transactions.index.datagrid.paid').'</p>';

                    case self::STATUS_PENDING:
                        return '<p class="label-pending">'.trans('admin::app.sales.transactions.index.datagrid.pending').'</p>';

                    case self::STATUS_COMPLETED:
                        return '<p class="label-completed">'.trans('admin::app.sales.transactions.index.datagrid.completed').'</p>';
                }
            },
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('admin::app.sales.transactions.index.datagrid.transaction-date'),
            'type' => 'date',
            'searchable' => true,
            'filterable' => true,
            'filterable_type' => 'date_range',
            'sortable' => true,
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('sales.shipments.view')) {
            $this->addAction([
                'icon' => 'icon-view',
                'title' => trans('admin::app.sales.transactions.index.datagrid.view'),
                'method' => 'GET',
                'url' => function ($row) {
                    return route('admin.sales.transactions.view', $row->id);
                },
            ]);
        }
    }
}
