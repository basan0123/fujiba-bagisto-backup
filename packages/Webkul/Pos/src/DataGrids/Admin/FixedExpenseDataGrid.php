<?php

namespace Webkul\Pos\DataGrids\Admin;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class FixedExpenseDataGrid extends DataGrid
{
    protected $primaryColumn = 'expense_id';

    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('pos_fixed_expenses as fe')
            ->leftJoin('pos_outlets', 'fe.outlet_id', '=', 'pos_outlets.id')
            ->select(
                'fe.id as expense_id',
                'fe.name',
                'fe.category',
                'fe.amount',
                'fe.currency',
                'fe.billing_day',
                'fe.is_active',
                'pos_outlets.name as outlet_name'
            );

        $this->addFilter('expense_id', 'fe.id');
        $this->addFilter('is_active', 'fe.is_active');
        $this->addFilter('outlet_name', 'pos_outlets.name');

        return $queryBuilder;
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'expense_id',
            'label'      => trans('pos::app.admin.fixed-expenses.index.datagrid.id'),
            'type'       => 'integer',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('pos::app.admin.fixed-expenses.index.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'category',
            'label'      => trans('pos::app.admin.fixed-expenses.index.datagrid.category'),
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => false,
        ]);

        $this->addColumn([
            'index'    => 'amount',
            'label'    => trans('pos::app.admin.fixed-expenses.index.datagrid.amount'),
            'type'     => 'decimal',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index'    => 'billing_day',
            'label'    => trans('pos::app.admin.fixed-expenses.index.datagrid.billing_day'),
            'type'     => 'integer',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index'    => 'is_active',
            'label'    => trans('pos::app.admin.fixed-expenses.index.datagrid.active'),
            'type'     => 'boolean',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index'    => 'outlet_name',
            'label'    => trans('pos::app.admin.fixed-expenses.index.datagrid.outlet'),
            'type'     => 'string',
            'sortable' => false,
        ]);
    }

    public function prepareActions(): void
    {
        $this->addAction([
            'index'  => 'edit',
            'icon'   => 'icon-edit',
            'title'  => trans('pos::app.admin.fixed-expenses.index.datagrid.edit'),
            'method' => 'GET',
            'url'    => fn ($row) => route('admin.pos.fixed-expenses.edit', $row->expense_id),
        ]);

        $this->addAction([
            'index'  => 'delete',
            'icon'   => 'icon-delete',
            'title'  => trans('pos::app.admin.fixed-expenses.index.datagrid.delete'),
            'method' => 'DELETE',
            'url'    => fn ($row) => route('admin.pos.fixed-expenses.destroy', $row->expense_id),
        ]);
    }
}
