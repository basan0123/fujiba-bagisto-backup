<?php

namespace Webkul\Pos\DataGrids\Admin;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class ProductRequestDataGrid extends DataGrid
{
    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'request_id';

    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('pos_product_request')
            ->leftJoin('products', 'pos_product_request.product_id', '=', 'products.id')
            ->leftJoin('product_images', 'products.id', '=', 'product_images.product_id')
            ->leftJoin('product_flat', 'product_flat.product_id', '=', 'products.id')
            ->leftJoin('pos_users', 'pos_users.id', '=', 'pos_product_request.user_id')
            ->leftJoin('pos_outlets', 'pos_outlets.id', '=', 'pos_users.outlet_id')
            ->select(
                'pos_product_request.id as request_id',
                'products.id as product_id',
                'product_images.path as product_image',
                'product_flat.name as product_name',
                'pos_outlets.name as outlet_name',
                'pos_product_request.created_at as request_date',
                'pos_product_request.requested_quantity as quantity',
                'pos_product_request.request_status as request_status',
                'pos_product_request.comment as comment'
            )
            ->addSelect(DB::raw('CONCAT('.DB::getTablePrefix()."pos_users.firstname, ' ', ".DB::getTablePrefix().'pos_users.lastname) as user_name'))
            ->where('pos_product_request.send_status', 1)
            ->where('product_flat.locale', app()->getLocale())
            ->groupBy('pos_product_request.id');

        $this->addFilter('request_id', 'pos_product_request.id');
        $this->addFilter('product_id', 'pos_product_request.product_id');
        $this->addFilter('product_name', 'product_flat.name');
        $this->addFilter('user_name', DB::raw('CONCAT('.DB::getTablePrefix()."pos_users.firstname, ' ', ".DB::getTablePrefix().'pos_users.lastname)'));
        $this->addFilter('outlet_name', 'pos_outlets.name');
        $this->addFilter('request_date', 'pos_product_request.created_at');
        $this->addFilter('quantity', 'pos_product_request.requested_quantity');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'request_id',
            'label'      => trans('pos::app.admin.requests.index.datagrid.id'),
            'type'       => 'integer',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index' => 'product_image',
            'label' => trans('pos::app.admin.requests.index.datagrid.product-image'),
            'type'  => 'string',
        ]);

        $this->addColumn([
            'index'      => 'product_name',
            'label'      => trans('pos::app.admin.requests.index.datagrid.product-name'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'user_name',
            'label'      => trans('pos::app.admin.requests.index.datagrid.user-name'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'outlet_name',
            'label'      => trans('pos::app.admin.requests.index.datagrid.outlet-name'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'quantity',
            'label'      => trans('pos::app.admin.requests.index.datagrid.requested-qty'),
            'type'       => 'integer',
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'request_date',
            'label'      => trans('pos::app.admin.requests.index.datagrid.request-date'),
            'type'       => 'datetime',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'              => 'request_status',
            'label'              => trans('pos::app.admin.requests.index.datagrid.status.title'),
            'type'               => 'string',
            'filterable'         => true,
            'sortable'           => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => [
                [
                    'label'  => trans('pos::app.admin.requests.index.datagrid.status.options.pending'),
                    'value'  => 0,
                ],
                [
                    'label'  => trans('pos::app.admin.requests.index.datagrid.status.options.complete'),
                    'value'  => 1,
                ],
                [
                    'label'  => trans('pos::app.admin.requests.index.datagrid.status.options.decline'),
                    'value'  => 2,
                ],
            ],
        ]);
    }

    /**
     * Prepare Actions
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('pos.requests.view')) {
            $this->addAction([
                'icon'   => 'icon-view',
                'title'  => trans('pos::app.admin.requests.index.datagrid.view'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.pos.requests.view', $row->request_id);
                },
            ]);
        }
    }

    /**
     * Prepare Mass Actions
     */
    public function prepareMassActions(): void
    {
        if (bouncer()->hasPermission('pos.requests.view')) {
            $this->addMassAction([
                'title'   => trans('pos::app.admin.requests.index.datagrid.update-status'),
                'url'     => route('admin.pos.requests.mass_update'),
                'method'  => 'POST',
                'options' => [
                    [
                        'label' => trans('pos::app.admin.requests.index.datagrid.status.options.complete'),
                        'value' => 1,
                    ],
                    [
                        'label' => trans('pos::app.admin.requests.index.datagrid.status.options.decline'),
                        'value' => 2,
                    ],
                    [
                        'label' => trans('pos::app.admin.requests.index.datagrid.status.options.pending'),
                        'value' => 0,
                    ],
                ],
            ]);
        }
    }
}
