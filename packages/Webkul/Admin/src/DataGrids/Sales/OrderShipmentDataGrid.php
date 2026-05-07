<?php

namespace Webkul\Admin\DataGrids\Sales;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\DataGrid\DataGrid;
use Webkul\Sales\Models\OrderAddress;

class OrderShipmentDataGrid extends DataGrid
{
    /**
     * Shipment Id.
     *
     * @var string
     */
    protected $primaryColumn = 'shipment_id';

    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $tablePrefix = DB::getTablePrefix();

        $queryBuilder = DB::table('shipments')
            ->leftJoin('addresses as order_address_shipping', function ($leftJoin) {
                $leftJoin->on('order_address_shipping.order_id', '=', 'shipments.order_id')
                    ->where('order_address_shipping.address_type', OrderAddress::ADDRESS_TYPE_SHIPPING);
            })
            ->leftJoin('orders', 'shipments.order_id', '=', 'orders.id')
            ->leftJoin('customers', 'orders.customer_id', '=', 'customers.id')
            ->leftJoin('inventory_sources', 'shipments.inventory_source_id', '=', 'inventory_sources.id')
            ->select(
                'shipments.id as shipment_id',
                'orders.increment_id as shipment_order_id',
                'shipments.total_qty as shipment_total_qty',
                'orders.created_at as order_date',
                'shipments.created_at as shipment_created_at',
                'orders.channel_id as channel_id',
                'orders.channel_name as channel_name',
                'customers.image as customer_image'
            )
            ->addSelect(DB::raw('CONCAT(COALESCE('.$tablePrefix.'orders.customer_first_name, ""), " ", COALESCE('.$tablePrefix.'orders.customer_last_name, "")) as customer_full_name'))
            ->addSelect(DB::raw('CONCAT('.$tablePrefix.'order_address_shipping.first_name, " ", '.$tablePrefix.'order_address_shipping.last_name) as shipped_to'))
            ->selectRaw('IF('.$tablePrefix.'shipments.inventory_source_id IS NOT NULL,'.$tablePrefix.'inventory_sources.name, '.$tablePrefix.'shipments.inventory_source_name) as inventory_source_name');

        $this->addFilter('shipment_id', 'shipments.id');
        $this->addFilter('shipment_order_id', 'orders.increment_id');
        $this->addFilter('shipment_total_qty', 'shipments.total_qty');
        $this->addFilter('inventory_source_name', DB::raw('IF('.$tablePrefix.'shipments.inventory_source_id IS NOT NULL,'.$tablePrefix.'inventory_sources.name, '.$tablePrefix.'shipments.inventory_source_name)'));
        $this->addFilter('order_date', 'orders.created_at');
        $this->addFilter('shipment_created_at', 'shipments.created_at');
        $this->addFilter('shipped_to', DB::raw('CONCAT('.$tablePrefix.'order_address_shipping.first_name, " ", '.$tablePrefix.'order_address_shipping.last_name)'));
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
            'index' => 'shipment_id',
            'label' => trans('admin::app.sales.shipments.index.datagrid.id'),
            'type' => 'integer',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'shipment_order_id',
            'label' => trans('admin::app.sales.shipments.index.datagrid.order-id'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'shipment_total_qty',
            'label' => trans('admin::app.sales.shipments.index.datagrid.total-qty'),
            'type' => 'integer',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'inventory_source_name',
            'label' => trans('admin::app.sales.shipments.index.datagrid.inventory-source'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'shipped_to',
            'label' => trans('admin::app.sales.shipments.index.datagrid.shipment-to'),
            'type' => 'string',
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
            'index' => 'order_date',
            'label' => trans('admin::app.sales.shipments.index.datagrid.order-date'),
            'type' => 'date',
            'filterable' => true,
            'filterable_type' => 'date_range',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'shipment_created_at',
            'label' => trans('admin::app.sales.shipments.index.datagrid.shipment-date'),
            'type' => 'date',
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
                'title' => trans('admin::app.sales.shipments.index.datagrid.view'),
                'method' => 'GET',
                'url' => function ($row) {
                    return route('admin.sales.shipments.view', $row->shipment_id);
                },
            ]);
        }
    }
}
