<?php

namespace Webkul\Pos\DataGrids\Admin;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class OutletDataGrid extends DataGrid
{
    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'outlet_id';

    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('pos_outlets')
            ->leftJoin('inventory_sources', 'pos_outlets.inventory_source_id', '=', 'inventory_sources.id')
            ->leftJoin('pos_receipts', 'pos_outlets.receipt_id', '=', 'pos_receipts.id')
            ->addSelect(
                'pos_outlets.id as outlet_id',
                'pos_outlets.name as outlet_name',
                'pos_outlets.status as outlet_status',
                'inventory_sources.name as inventory_source',
                'pos_receipts.title as receipt_title'
            );

        $this->addFilter('outlet_id', 'pos_outlets.id');
        $this->addFilter('outlet_name', 'pos_outlets.name');
        $this->addFilter('outlet_status', 'pos_outlets.status');
        $this->addFilter('inventory_source', 'inventory_sources.name');
        $this->addFilter('receipt_title', 'pos_receipts.title');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'outlet_id',
            'label'      => trans('pos::app.admin.users.outlets.index.datagrid.id'),
            'type'       => 'integer',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'outlet_name',
            'label'      => trans('pos::app.admin.users.outlets.index.datagrid.name'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'inventory_source',
            'label'      => trans('pos::app.admin.users.outlets.index.datagrid.inventory-source'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'receipt_title',
            'label'      => trans('pos::app.admin.users.outlets.index.datagrid.receipt-title'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'              => 'outlet_status',
            'label'              => trans('pos::app.admin.users.outlets.index.datagrid.status'),
            'type'               => 'string',
            'filterable'         => true,
            'sortable'           => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => [
                [
                    'label'  => trans('pos::app.admin.users.outlets.index.datagrid.active'),
                    'value'  => 1,
                ],
                [
                    'label'  => trans('pos::app.admin.users.outlets.index.datagrid.inactive'),
                    'value'  => 0,
                ],
            ],
            'closure'            => function ($row) {
                if ($row->outlet_status) {
                    return '<p class="label-active">'.trans('pos::app.admin.users.outlets.index.datagrid.active').'</p>';
                }

                return '<p class="label-info">'.trans('pos::app.admin.users.outlets.index.datagrid.inactive').'</p>';
            },
        ]);
    }

    /**
     * Prepare Actions
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('pos.users.outlets.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('pos::app.admin.users.outlets.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.pos.outlets.edit', $row->outlet_id);
                },
            ]);
        }

        if (bouncer()->hasPermission('pos.users.outlets.assign_products')) {
            $this->addAction([
                'icon'   => 'pos-listing-icon',
                'title'  => trans('pos::app.admin.users.outlets.index.datagrid.assign'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.pos.outlets.assign_products', $row->outlet_id);
                },
            ]);
        }

        if (bouncer()->hasPermission('pos.users.outlets.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('pos::app.admin.users.outlets.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.pos.outlets.delete', $row->outlet_id);
                },
            ]);
        }
    }

    /**
     * Prepare Mass Actions
     */
    public function prepareMassActions(): void
    {
        if (bouncer()->hasPermission('pos.users.outlets.delete')) {
            $this->addMassAction([
                'title'  => trans('pos::app.admin.users.outlets.index.datagrid.delete'),
                'method' => 'POST',
                'url'    => route('admin.pos.outlets.mass_delete'),
            ]);
        }

        if (bouncer()->hasPermission('pos.users.outlets.edit')) {
            $this->addMassAction([
                'title'   => trans('pos::app.admin.users.outlets.index.datagrid.update-status'),
                'url'     => route('admin.pos.outlets.mass_update'),
                'method'  => 'POST',
                'options' => [
                    [
                        'label' => trans('pos::app.admin.users.outlets.index.datagrid.active'),
                        'value' => 1,
                    ], [
                        'label' => trans('pos::app.admin.users.outlets.index.datagrid.inactive'),
                        'value' => 0,
                    ],
                ],
            ]);
        }
    }
}
