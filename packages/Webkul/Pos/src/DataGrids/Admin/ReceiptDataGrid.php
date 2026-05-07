<?php

namespace Webkul\Pos\DataGrids\Admin;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class ReceiptDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        return DB::table('pos_receipts');
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('pos::app.admin.receipts.index.datagrid.id'),
            'type'       => 'integer',
            'searchable' => true,
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'title',
            'label'      => trans('pos::app.admin.receipts.index.datagrid.title'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'              => 'status',
            'label'              => trans('pos::app.admin.receipts.index.datagrid.status.title'),
            'type'               => 'string',
            'searchable'         => true,
            'filterable'         => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => [
                [
                    'label'  => trans('pos::app.admin.receipts.index.datagrid.status.options.active'),
                    'value'  => 1,
                ],
                [
                    'label'  => trans('pos::app.admin.receipts.index.datagrid.status.options.inactive'),
                    'value'  => 0,
                ],
            ],
            'sortable'           => true,
            'closure'            => function ($row) {
                if ($row->status) {
                    return '<p class="label-active">'.trans('pos::app.admin.receipts.index.datagrid.status.options.active').'</p>';
                }

                return '<p class="label-info">'.trans('pos::app.admin.receipts.index.datagrid.status.options.inactive').'</p>';
            },
        ]);

        if (bouncer()->hasPermission('pos.receipts.preview')) {
            $this->addColumn([
                'index'   => 'preview',
                'label'   => trans('pos::app.admin.receipts.index.datagrid.preview'),
                'type'    => 'string',
                'closure' => function ($row) {
                    return '<div class="flex"><a target="_blank" class="secondary-button" href="'.route('admin.pos.receipts.show', $row->id).'">'.trans('pos::app.admin.receipts.index.datagrid.preview').'</a><div>';
                },
            ]);
        }
    }

    /**
     * Prepare Actions
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('pos.receipts.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('pos::app.admin.receipts.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.pos.receipts.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('pos.receipts.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('pos::app.admin.receipts.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.pos.receipts.delete', $row->id);
                },
            ]);
        }
    }

    /**
     * Prepare Mass Actions
     */
    public function prepareMassActions(): void
    {
        if (bouncer()->hasPermission('pos.receipts.delete')) {
            $this->addMassAction([
                'title'  => trans('pos::app.admin.receipts.index.datagrid.delete'),
                'url'    => route('admin.pos.receipts.mass_delete'),
                'method' => 'POST',
            ]);
        }
    }
}
