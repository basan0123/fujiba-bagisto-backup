<?php

namespace Webkul\Pos\DataGrids\Admin;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class UserDataGrid extends DataGrid
{
    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'user_id';

    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('pos_users')
            ->leftJoin('pos_outlets', 'pos_users.outlet_id', '=', 'pos_outlets.id')
            ->select(
                'pos_users.id as user_id',
                'pos_users.username as user_name',
                'pos_users.email as user_email',
                'pos_users.image as user_image',
                'pos_outlets.name as outlet_name',
                'pos_users.status as user_status'
            )
            ->addSelect(DB::raw('CONCAT(pos_users.firstname, " ", pos_users.lastname) as full_name'));

        $this->addFilter('user_id', 'pos_users.id');
        $this->addFilter('user_name', 'pos_users.username');
        $this->addFilter('user_email', 'pos_users.email');
        $this->addFilter('outlet_name', 'pos_outlets.name');
        $this->addFilter('user_status', 'pos_users.status');
        $this->addFilter('full_name', DB::raw('CONCAT(pos_users.firstname, " ", pos_users.lastname)'));

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'user_id',
            'label'      => trans('pos::app.admin.users.users.index.datagrid.id'),
            'type'       => 'integer',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index' => 'user_image',
            'label' => trans('pos::app.admin.users.users.index.datagrid.profile-image'),
            'type'  => 'string',
        ]);

        $this->addColumn([
            'index'      => 'user_name',
            'label'      => trans('pos::app.admin.users.users.index.datagrid.username'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'full_name',
            'label'      => trans('pos::app.admin.users.users.index.datagrid.full-name'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'user_email',
            'label'      => trans('pos::app.admin.users.users.index.datagrid.email'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'outlet_name',
            'label'      => trans('pos::app.admin.users.users.index.datagrid.outlet-name'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'              => 'user_status',
            'label'              => trans('pos::app.admin.users.users.index.datagrid.status.title'),
            'type'               => 'string',
            'filterable'         => true,
            'sortable'           => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => [
                [
                    'label'  => trans('pos::app.admin.users.users.index.datagrid.status.options.active'),
                    'value'  => 1,
                ],
                [
                    'label'  => trans('pos::app.admin.users.users.index.datagrid.status.options.disable'),
                    'value'  => 0,
                ],
            ],
        ]);
    }

    /**
     * Prepare Actions.
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('pos.users.users.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('pos::app.admin.users.users.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.pos.users.edit', $row->user_id);
                },
            ]);
        }

        if (bouncer()->hasPermission('pos.users.users.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('pos::app.admin.users.users.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.pos.users.delete', $row->user_id);
                },
            ]);
        }
    }

    /**
     * Prepare Mass Actions.
     */
    public function prepareMassActions(): void
    {
        if (bouncer()->hasPermission('pos.users.users.delete')) {
            $this->addMassAction([
                'title'  => trans('pos::app.admin.users.users.index.datagrid.delete'),
                'url'    => route('admin.pos.users.mass_delete'),
                'method' => 'POST',
            ]);
        }

        if (bouncer()->hasPermission('pos.users.users.edit')) {
            $this->addMassAction([
                'title'   => trans('pos::app.admin.users.users.index.datagrid.update-status'),
                'url'     => route('admin.pos.users.mass_update'),
                'method'  => 'POST',
                'options' => [
                    [
                        'label' => trans('pos::app.admin.users.users.index.datagrid.status.options.active'),
                        'value' => 1,
                    ],
                    [
                        'label' => trans('pos::app.admin.users.users.index.datagrid.status.options.disable'),
                        'value' => 0,
                    ],
                ],
            ]);
        }
    }
}
