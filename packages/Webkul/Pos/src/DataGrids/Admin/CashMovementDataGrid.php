<?php

namespace Webkul\Pos\DataGrids\Admin;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class CashMovementDataGrid extends DataGrid
{
    protected $primaryColumn = 'movement_id';

    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('pos_cash_movements as cm')
            ->leftJoin('pos_outlets', 'cm.outlet_id', '=', 'pos_outlets.id')
            ->leftJoin('pos_users', 'cm.user_id', '=', 'pos_users.id')
            ->select(
                'cm.id as movement_id',
                'cm.type',
                'cm.category',
                'cm.description',
                'cm.amount',
                'cm.currency',
                'cm.movement_date',
                'cm.reference_number',
                'pos_outlets.name as outlet_name',
                DB::raw("CONCAT(pos_users.firstname, ' ', pos_users.lastname) as agent_name")
            );

        $this->addFilter('movement_id', 'cm.id');
        $this->addFilter('type', 'cm.type');
        $this->addFilter('movement_date', 'cm.movement_date');

        return $queryBuilder;
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'movement_id',
            'label'      => trans('pos::app.admin.cash-movements.index.datagrid.id'),
            'type'       => 'integer',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'type',
            'label'      => trans('pos::app.admin.cash-movements.index.datagrid.type'),
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'category',
            'label'      => trans('pos::app.admin.cash-movements.index.datagrid.category'),
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => false,
        ]);

        $this->addColumn([
            'index'    => 'description',
            'label'    => trans('pos::app.admin.cash-movements.index.datagrid.description'),
            'type'     => 'string',
            'sortable' => false,
        ]);

        $this->addColumn([
            'index'    => 'amount',
            'label'    => trans('pos::app.admin.cash-movements.index.datagrid.amount'),
            'type'     => 'decimal',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index'           => 'movement_date',
            'label'           => trans('pos::app.admin.cash-movements.index.datagrid.date'),
            'type'            => 'date',
            'filterable'      => true,
            'filterable_type' => 'date_range',
            'sortable'        => true,
        ]);

        $this->addColumn([
            'index'    => 'outlet_name',
            'label'    => trans('pos::app.admin.cash-movements.index.datagrid.outlet'),
            'type'     => 'string',
            'sortable' => false,
        ]);

        $this->addColumn([
            'index'    => 'agent_name',
            'label'    => trans('pos::app.admin.cash-movements.index.datagrid.agent'),
            'type'     => 'string',
            'sortable' => false,
        ]);
    }

    public function prepareActions(): void
    {
        $this->addAction([
            'index'  => 'delete',
            'icon'   => 'icon-delete',
            'title'  => trans('pos::app.admin.cash-movements.index.datagrid.delete'),
            'method' => 'DELETE',
            'url'    => fn ($row) => route('admin.pos.cash-movements.destroy', $row->movement_id),
        ]);
    }
}
