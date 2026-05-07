<?php

namespace Webkul\Pos\DataGrids\Admin;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\Inventory\Repositories\InventorySourceRepository;
use Webkul\Pos\Repositories\OutletProductRepository;
use Webkul\Pos\Repositories\OutletRepository;

class OutletProductDataGrid extends DataGrid
{
    /**
     * Current Outlet
     *
     * @var object
     */
    protected $outlet;

    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'product_id';

    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(
        protected OutletRepository $outletRepository,
        protected OutletProductRepository $outletProductRepository,
        protected InventorySourceRepository $inventorySourceRepository
    ) {}

    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $this->outlet = $this->outletRepository->findOrFail(request()->route('id'));

        // Default-scope to the channel whose code matches this outlet's name
        // (case-insensitive). Fujiba convention: outlet "MMFOOD" → channel "mmfood".
        // The user can still pick a different channel via ?product_channel=… or the
        // filter dropdown — and explicit ?product_channel= (empty) means "all".
        $requestedChannel = request('product_channel');
        if ($requestedChannel === null) {
            $match = DB::table('channels')
                ->whereRaw('LOWER(code) = ?', [mb_strtolower($this->outlet->name)])
                ->value('code');
            $effectiveChannel = $match ?: '';
        } else {
            $effectiveChannel = (string) $requestedChannel;
        }

        $queryBuilder = DB::table('product_flat')
            ->leftJoin('products', 'product_flat.product_id', '=', 'products.id')
            ->leftJoin('product_images', 'products.id', '=', 'product_images.product_id')
            ->leftJoin('product_inventories', 'product_flat.product_id', '=', 'product_inventories.product_id')
            ->leftJoin('inventory_sources', 'product_inventories.inventory_source_id', '=', 'inventory_sources.id')
            ->leftJoin('pos_outlet_product', function ($qb) {
                $qb->on('pos_outlet_product.product_id', '=', 'products.id')
                    ->leftJoin('pos_outlets', 'pos_outlet_product.outlet_id', '=', 'pos_outlets.id')
                    ->where('pos_outlet_product.outlet_id', $this->outlet->id);
            })
            ->addSelect(
                'products.type',
                'product_flat.name as product_name',
                'product_flat.sku as product_sku',
                'product_flat.price as product_price',
                'product_flat.status as product_status',
                DB::raw('MIN('.DB::getTablePrefix().'product_flat.channel) as product_channel'),
                'pos_outlet_product.status as pos_status',
                DB::raw(DB::getTablePrefix().'products.id as product_id'),
                DB::raw(DB::getTablePrefix().'product_images.path as product_image'),
                DB::raw('SUM('.DB::getTablePrefix().'product_inventories.qty) as product_quantity'),
                DB::raw('GROUP_CONCAT(DISTINCT '.DB::getTablePrefix().'inventory_sources.name ORDER BY '.DB::getTablePrefix().'inventory_sources.name SEPARATOR ", ") as inventory_source_names'),
            )
            ->whereNull('products.parent_id')
            ->where('product_flat.locale', app()->getLocale())
            ->groupBy('product_id');

        // Channel filter — defaults to the outlet's matching channel, but can
        // be overridden (or cleared) via the dropdown.
        if ($effectiveChannel !== '') {
            $queryBuilder->where('product_flat.channel', $effectiveChannel);
        }

        // Inventory source filter
        if ($invSrcId = request('inventory_source_id')) {
            $queryBuilder->where('product_inventories.inventory_source_id', $invSrcId);
        }

        $this->addFilter('product_id', 'products.id');
        $this->addFilter('type', 'products.type');
        $this->addFilter('product_name', 'product_flat.name');
        $this->addFilter('product_sku', 'product_flat.sku');
        $this->addFilter('product_price', 'product_flat.price');
        $this->addFilter('product_quantity', 'product_inventories.qty');
        $this->addFilter('product_status', 'product_flat.status');
        $this->addFilter('pos_status', 'pos_outlet_product.status');
        $this->addFilter('product_channel', 'product_flat.channel');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'product_id',
            'label'      => trans('pos::app.admin.users.outlets.assign.datagrid.id'),
            'type'       => 'integer',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index' => 'product_image',
            'label' => trans('pos::app.admin.users.outlets.assign.datagrid.image'),
            'type'  => 'string',
        ]);

        $this->addColumn([
            'index'      => 'product_name',
            'label'      => trans('pos::app.admin.users.outlets.assign.datagrid.name'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'product_sku',
            'label'      => trans('pos::app.admin.users.outlets.assign.datagrid.sku'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'              => 'type',
            'label'              => trans('pos::app.admin.users.outlets.assign.datagrid.type'),
            'type'               => 'string',
            'filterable'         => true,
            'sortable'           => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => collect(config('product_types'))
                ->map(fn ($type) => ['label' => trans($type['name']), 'value' => $type['key']])
                ->values()
                ->toArray(),
        ]);

        $this->addColumn([
            'index'      => 'product_price',
            'label'      => trans('pos::app.admin.users.outlets.assign.datagrid.price'),
            'type'       => 'integer',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'product_quantity',
            'label'      => trans('pos::app.admin.users.outlets.assign.datagrid.qty'),
            'type'       => 'integer',
            'sortable'   => true,
            'filterable' => true,
            'closure'    => function ($row) {
                return $this->outletProductRepository->getProductTotalQty(
                    $row->product_id,
                    $this->outlet->inventory_source_id
                );
            },
        ]);

        $this->addColumn([
            'index'              => 'product_status',
            'label'              => trans('pos::app.admin.users.outlets.assign.datagrid.status'),
            'type'               => 'string',
            'filterable'         => true,
            'sortable'           => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => [
                [
                    'label'  => trans('pos::app.admin.users.outlets.assign.datagrid.active'),
                    'value'  => 1,
                ],
                [
                    'label'  => trans('pos::app.admin.users.outlets.assign.datagrid.disable'),
                    'value'  => 0,
                ],
            ],
        ]);

        $this->addColumn([
            'index'              => 'pos_status',
            'label'              => trans('pos::app.admin.users.outlets.assign.datagrid.pos-status'),
            'type'               => 'string',
            'filterable'         => true,
            'sortable'           => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => [
                [
                    'label'  => trans('pos::app.admin.users.outlets.assign.datagrid.active'),
                    'value'  => 1,
                ],
                [
                    'label'  => trans('pos::app.admin.users.outlets.assign.datagrid.disable'),
                    'value'  => 0,
                ],
            ],
        ]);

        $channels = core()->getAllChannels();
        $this->addColumn([
            'index'              => 'product_channel',
            'label'              => trans('pos::app.admin.users.outlets.assign.datagrid.channel'),
            'type'               => 'string',
            'filterable'         => true,
            'sortable'           => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => $channels
                ->map(fn ($ch) => ['label' => $ch->name, 'value' => $ch->code])
                ->values()
                ->toArray(),
        ]);

        $invSources = $this->inventorySourceRepository->findWhere(['status' => 1]);
        $this->addColumn([
            'index'              => 'inventory_source_names',
            'label'              => trans('pos::app.admin.users.outlets.assign.datagrid.inventory-source'),
            'type'               => 'string',
            'sortable'           => false,
            'filterable'         => false,
        ]);
    }

    /**
     * Prepare Mass Actions
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('pos.users.outlets.assign_products')) {
            $this->addMassAction([
                'type'    => 'update',
                'title'   => trans('pos::app.admin.users.outlets.assign.datagrid.update-assign'),
                'url'     => route('admin.pos.outlets.assign_products.mass_assign', request()->route('id')),
                'method'  => 'POST',
                'options' => [
                    [
                        'label' => trans('pos::app.admin.users.outlets.assign.datagrid.assign'),
                        'value' => 1,
                    ], [
                        'label' => trans('pos::app.admin.users.outlets.assign.datagrid.unassign'),
                        'value' => 0,
                    ],
                ],
            ]);
        }
    }
}
