<?php

namespace Webkul\Admin\DataGrids\Catalog;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class StockAuditDataGrid extends DataGrid
{
    protected $primaryColumn = 'audit_id';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('product_stock_audits as psa')
            ->leftJoin('product_flat as pf', function ($j) {
                $j->on('pf.product_id', '=', 'psa.product_id')
                    ->where('pf.locale', '=', app()->getLocale());
            })
            ->leftJoin('inventory_sources as is_', 'is_.id', '=', 'psa.inventory_source_id')
            ->leftJoin('admins as a', 'a.id', '=', 'psa.admin_id')
            ->leftJoin('admins as ar', 'ar.id', '=', 'psa.remark_updated_by')
            ->select(
                'psa.id as audit_id',
                'psa.created_at',
                'psa.product_id',
                'pf.sku',
                'pf.name as product_name',
                'pf.channel',
                'psa.inventory_source_id',
                'is_.name as source_name',
                'psa.qty_before',
                'psa.qty_after',
                'psa.delta',
                'psa.source_action',
                'psa.admin_id',
                'a.name as admin_name',
                'psa.remark',
                'psa.remark_updated_at',
                'ar.name as remark_updater_name',
            );

        $this->addFilter('audit_id', 'psa.id');
        $this->addFilter('product_id', 'psa.product_id');
        $this->addFilter('inventory_source_id', 'psa.inventory_source_id');
        $this->addFilter('admin_id', 'psa.admin_id');
        $this->addFilter('channel', 'pf.channel');
        $this->addFilter('source_action', 'psa.source_action');
        $this->addFilter('created_at', 'psa.created_at');

        return $queryBuilder;
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index' => 'audit_id',
            'label' => 'ID',
            'type' => 'integer',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => 'Date / Time',
            'type' => 'datetime',
            'sortable' => true,
            'filterable' => true,
            'filterable_type' => 'datetime_range',
        ]);

        $this->addColumn([
            'index' => 'sku',
            'label' => 'SKU',
            'type' => 'string',
            'sortable' => true,
            'searchable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'product_name',
            'label' => 'Product',
            'type' => 'string',
            'sortable' => true,
            'searchable' => true,
            'filterable' => true,
        ]);

        $channels = core()->getAllChannels();
        if ($channels->count() > 1) {
            $this->addColumn([
                'index' => 'channel',
                'label' => 'Channel',
                'type' => 'string',
                'sortable' => true,
                'filterable' => true,
                'filterable_type' => 'dropdown',
                'filterable_options' => collect($channels)
                    ->map(fn ($c) => ['label' => $c->name, 'value' => $c->code])
                    ->values()
                    ->toArray(),
            ]);
        }

        $this->addColumn([
            'index' => 'source_name',
            'label' => 'Source',
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'qty_before',
            'label' => 'From',
            'type' => 'integer',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'qty_after',
            'label' => 'To',
            'type' => 'integer',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'delta',
            'label' => 'Δ',
            'type' => 'integer',
            'sortable' => true,
            'closure' => function ($row) {
                $sign = $row->delta > 0 ? '+' : '';
                $color = $row->delta > 0 ? '#15803d' : '#b91c1c';
                return '<span style="color:'.$color.';font-weight:600">'.$sign.$row->delta.'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'admin_name',
            'label' => 'Admin',
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'source_action',
            'label' => 'Action',
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'remark',
            'label' => 'Remark',
            'type' => 'string',
            'searchable' => true,
            'closure' => function ($row) {
                $r = e($row->remark ?? '');
                $meta = '';
                if ($row->remark_updated_at) {
                    $meta = '<div style="font-size:11px;color:#64748b">'.e($row->remark_updater_name ?? '?').' · '.e($row->remark_updated_at).'</div>';
                }
                return '<div>'.$r.$meta.'</div>';
            },
        ]);
    }

    public function prepareActions()
    {
        $this->addAction([
            'icon' => 'icon-edit',
            'title' => 'Edit Remark',
            'method' => 'GET',
            'url' => function ($row) {
                return route('admin.catalog.track.remark.edit', $row->audit_id);
            },
        ]);
    }

    public function prepareMassActions()
    {
        $this->addMassAction([
            'title' => 'Set Remark (preset)',
            'method' => 'POST',
            'url' => route('admin.catalog.track.remark.mass_update'),
            'options' => [
                ['label' => 'Lost', 'value' => 'Lost'],
                ['label' => 'Damaged', 'value' => 'Damaged'],
                ['label' => 'Manual count adjustment', 'value' => 'Manual count adjustment'],
                ['label' => 'Returned to supplier', 'value' => 'Returned to supplier'],
                ['label' => 'Stocktake correction', 'value' => 'Stocktake correction'],
                ['label' => 'Theft / Shrinkage', 'value' => 'Theft / Shrinkage'],
                ['label' => 'Promotional giveaway', 'value' => 'Promotional giveaway'],
                ['label' => 'Internal use', 'value' => 'Internal use'],
                ['label' => '— Clear remark —', 'value' => '__CLEAR__'],
            ],
        ]);
    }
}
