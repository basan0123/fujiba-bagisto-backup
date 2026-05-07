<?php

namespace Webkul\Pos\DataGrids\Admin;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class BankDataGrid extends DataGrid
{
    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'bank_id';

    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('pos_banks')
            ->leftJoin('pos_users', 'pos_banks.agent_id', '=', 'pos_users.id')
            ->select(
                'pos_banks.id as bank_id',
                'pos_banks.agent_id',
                'pos_banks.name as bank_name',
                'pos_banks.address as bank_address',
                'pos_banks.email as bank_email',
                'pos_banks.phone as bank_phone',
                'pos_banks.status as bank_status'
            )
            ->addSelect(DB::raw(DB::getTablePrefix().'CONCAT(pos_users.firstname, " ", pos_users.lastname) as agent_name'));

        $this->addFilter('bank_id', 'pos_banks.id');
        $this->addFilter('bank_name', 'pos_banks.name');
        $this->addFilter('bank_email', 'pos_banks.email');
        $this->addFilter('bank_phone', 'pos_banks.phone');
        $this->addFilter('bank_address', 'pos_banks.address');
        $this->addFilter('agent_name', DB::raw(DB::getTablePrefix().'CONCAT(pos_users.firstname, " ", pos_users.lastname)'));
        $this->addFilter('bank_status', 'pos_banks.status');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'bank_id',
            'label'      => trans('pos::app.admin.banks.index.datagrid.id'),
            'type'       => 'integer',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'bank_name',
            'label'      => trans('pos::app.admin.banks.index.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'bank_address',
            'label'      => trans('pos::app.admin.banks.index.datagrid.address'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'agent_name',
            'label'      => trans('pos::app.admin.banks.index.datagrid.agent-name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                if ($row->agent_id) {
                    return '<a href="'.route('admin.pos.users.edit', $row->agent_id).'">'.$row->agent_name.'</a>';
                }

                return '-';
            },
        ]);

        $this->addColumn([
            'index'              => 'bank_status',
            'label'              => trans('pos::app.admin.banks.index.datagrid.status'),
            'type'               => 'string',
            'searchable'         => true,
            'filterable'         => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => [
                [
                    'label'  => trans('pos::app.admin.banks.index.datagrid.active'),
                    'value'  => 1,
                ],
                [
                    'label'  => trans('pos::app.admin.banks.index.datagrid.disable'),
                    'value'  => 0,
                ],
            ],
            'sortable'           => true,
            'closure'            => function ($row) {
                if ($row->bank_status) {
                    return '<p class="label-active">'.trans('pos::app.admin.banks.index.datagrid.active').'</p>';
                }

                return '<p class="label-info">'.trans('pos::app.admin.banks.index.datagrid.disable').'</p>';
            },
        ]);
    }

    /**
     * Prepare Actions
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('pos.banks.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('pos::app.admin.banks.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.pos.banks.edit', $row->bank_id);
                },
            ]);
        }

        if (bouncer()->hasPermission('pos.banks.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('pos::app.admin.banks.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.pos.banks.delete', $row->bank_id);
                },
            ]);
        }
    }

    /**
     * Prepare Mass Actions
     */
    public function prepareMassActions(): void
    {
        if (bouncer()->hasPermission('pos.banks.delete')) {
            $this->addMassAction([
                'title'  => trans('pos::app.admin.banks.index.datagrid.delete'),
                'method' => 'POST',
                'url'    => route('admin.pos.banks.mass_delete'),
            ]);
        }
    }
}
