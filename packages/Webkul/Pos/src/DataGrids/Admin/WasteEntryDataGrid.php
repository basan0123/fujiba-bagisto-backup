<?php

namespace Webkul\Pos\DataGrids\Admin;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class WasteEntryDataGrid extends DataGrid
{
    protected $primaryColumn = 'waste_id';

    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('pos_waste_entries as we')
            ->leftJoin('pos_outlets', 'we.outlet_id', '=', 'pos_outlets.id')
            ->leftJoin('pos_users', 'we.user_id', '=', 'pos_users.id')
            ->select(
                'we.id as waste_id',
                'we.product_name',
                'we.product_sku',
                'we.quantity',
                'we.unit_cost',
                'we.total_cost',
                'we.reason',
                'we.status',
                'we.wasted_at',
                'we.notes',
                'pos_outlets.name as outlet_name',
                DB::raw("CONCAT(pos_users.firstname, ' ', pos_users.lastname) as agent_name")
            );

        $this->addFilter('waste_id', 'we.id');
        $this->addFilter('status', 'we.status');
        $this->addFilter('wasted_at', 'we.wasted_at');
        $this->addFilter('outlet_name', 'pos_outlets.name');

        return $queryBuilder;
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'waste_id',
            'label'      => trans('pos::app.admin.waste.index.datagrid.id'),
            'type'       => 'integer',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'product_name',
            'label'      => trans('pos::app.admin.waste.index.datagrid.product'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'product_sku',
            'label'      => trans('pos::app.admin.waste.index.datagrid.sku'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => false,
        ]);

        $this->addColumn([
            'index'    => 'quantity',
            'label'    => trans('pos::app.admin.waste.index.datagrid.qty'),
            'type'     => 'decimal',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index'    => 'total_cost',
            'label'    => trans('pos::app.admin.waste.index.datagrid.total_cost'),
            'type'     => 'decimal',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index'      => 'reason',
            'label'      => trans('pos::app.admin.waste.index.datagrid.reason'),
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => false,
        ]);

        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('pos::app.admin.waste.index.datagrid.status'),
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'           => 'wasted_at',
            'label'           => trans('pos::app.admin.waste.index.datagrid.date'),
            'type'            => 'date',
            'filterable'      => true,
            'filterable_type' => 'date_range',
            'sortable'        => true,
        ]);

        $this->addColumn([
            'index'      => 'outlet_name',
            'label'      => trans('pos::app.admin.waste.index.datagrid.outlet'),
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => false,
        ]);

        $this->addColumn([
            'index'    => 'agent_name',
            'label'    => trans('pos::app.admin.waste.index.datagrid.agent'),
            'type'     => 'string',
            'sortable' => false,
        ]);
    }

    public function prepareActions(): void
    {
        $this->addAction([
            'index'  => 'verify',
            'icon'   => 'icon-eye',
            'title'  => trans('pos::app.admin.waste.index.datagrid.verify'),
            'method' => 'GET',
            'url'    => fn ($row) => route('admin.pos.waste.verify', $row->waste_id),
        ]);

        $this->addAction([
            'index'  => 'delete',
            'icon'   => 'icon-delete',
            'title'  => trans('pos::app.admin.waste.index.datagrid.delete'),
            'method' => 'DELETE',
            'url'    => fn ($row) => route('admin.pos.waste.destroy', $row->waste_id),
        ]);
    }
}
