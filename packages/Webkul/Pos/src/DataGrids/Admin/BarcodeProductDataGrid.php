<?php

namespace Webkul\Pos\DataGrids\Admin;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\DataGrid\DataGrid;

class BarcodeProductDataGrid extends DataGrid
{
    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'product_id';

    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('product_flat')
            ->leftJoin('products', 'product_flat.product_id', '=', 'products.id')
            ->leftJoin('product_images', 'products.id', '=', 'product_images.product_id')
            ->leftJoin('product_inventories', 'product_flat.product_id', '=', 'product_inventories.product_id')
            ->leftJoin('pos_product_barcode', 'pos_product_barcode.product_id', '=', 'products.id')
            ->addSelect(
                'products.id as product_id',
                'product_images.path as product_image',
                'product_flat.name as product_name',
                'product_flat.sku as product_sku',
                'pos_product_barcode.barcode',
                'pos_product_barcode.barcode_type',
                'pos_product_barcode.gtin',
                'product_flat.price as product_price',
                'product_flat.status',
                DB::raw('SUM('.DB::getTablePrefix().'product_inventories.qty) as product_quantity'),
            )
            ->whereNull('products.parent_id')
            ->where('product_flat.locale', app()->getLocale())
            ->groupBy('product_id');

        $this->addFilter('product_id', 'products.id');
        $this->addFilter('product_name', 'product_flat.name');
        $this->addFilter('product_sku', 'product_flat.sku');
        $this->addFilter('product_price', 'product_flat.price');
        $this->addFilter('product_quantity', 'product_inventories.qty');
        $this->addFilter('gtin', 'pos_product_barcode.gtin');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'product_id',
            'label'      => trans('pos::app.admin.barcode-products.index.datagrid.id'),
            'type'       => 'integer',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index' => 'product_image',
            'label' => trans('pos::app.admin.barcode-products.index.datagrid.image'),
            'type'  => 'string',
        ]);

        $this->addColumn([
            'index'      => 'product_name',
            'label'      => trans('pos::app.admin.barcode-products.index.datagrid.name'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'product_sku',
            'label'      => trans('pos::app.admin.barcode-products.index.datagrid.sku'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index' => 'barcode',
            'label' => trans('pos::app.admin.barcode-products.index.datagrid.barcode'),
            'type'  => 'string',
        ]);

        $this->addColumn([
            'index'      => 'gtin',
            'label'      => trans('pos::app.admin.barcode-products.index.datagrid.gtin'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'product_price',
            'label'      => trans('pos::app.admin.barcode-products.index.datagrid.price'),
            'type'       => 'integer',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'product_quantity',
            'label'      => trans('pos::app.admin.barcode-products.index.datagrid.qty'),
            'type'       => 'integer',
            'sortable'   => true,
            'filterable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'              => 'status',
            'label'              => trans('pos::app.admin.barcode-products.index.datagrid.status.title'),
            'type'               => 'string',
            'filterable'         => true,
            'sortable'           => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => [
                [
                    'label'  => trans('pos::app.admin.barcode-products.index.datagrid.status.options.active'),
                    'value'  => 1,
                ],
                [
                    'label'  => trans('pos::app.admin.barcode-products.index.datagrid.status.options.disable'),
                    'value'  => 0,
                ],
            ],
        ]);
    }

    /**
     * Inject tenant-aware barcode_url into each row after the query runs.
     */
    public function formatData(): array
    {
        $paginator = parent::formatData();

        foreach ($paginator['records'] as &$record) {
            $barcode   = is_array($record) ? ($record['barcode']    ?? null) : ($record->barcode    ?? null);
            $productId = is_array($record) ? ($record['product_id'] ?? null) : ($record->product_id ?? null);

            $barcodeUrl = null;

            if (! empty($barcode)) {
                $relativePath = "product/{$productId}/{$barcode}.png";

                if (Storage::disk('public')->exists($relativePath)) {
                    $barcodeUrl = Storage::disk('public')->url($relativePath);
                }
            }

            if (is_array($record)) {
                $record['barcode_url'] = $barcodeUrl;
            } else {
                $record->barcode_url = $barcodeUrl;
            }
        }

        return $paginator;
    }

    /**
     * Prepare Actions
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('pos.barcode_products.generate_barcode')) {
            $this->addAction([
                'icon'   => 'pos-barcode-icon',
                'title'  => trans('pos::app.admin.barcode-products.index.datagrid.generate-barcode'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.pos.barcode_products.generate_barcode', $row->product_id);
                },
            ]);
        }

        if (bouncer()->hasPermission('pos.barcode_products.print_barcode')) {
            $this->addAction([
                'icon'   => 'icon-printer',
                'title'  => trans('pos::app.admin.barcode-products.index.datagrid.print-barcode'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.pos.barcode_products.print_barcode', $row->product_id);
                },
            ]);
        }
    }

    /**
     * Prepare Mass Actions
     */
    public function prepareMassActions(): void
    {
        if (bouncer()->hasPermission(('pos.barcode_products.generate_barcode'))) {
            $this->addMassAction([
                'title'  => trans('pos::app.admin.barcode-products.index.datagrid.generate-barcode'),
                'method' => 'POST',
                'url'    => route('admin.pos.barcode_products.mass_generate_barcode'),
            ]);
        }

        if (bouncer()->hasPermission(('pos.barcode_products.print_barcode'))) {
            $this->addMassAction([
                'title'  => trans('pos::app.admin.barcode-products.index.datagrid.print-barcode'),
                'method' => 'POST',
                'url'    => route('admin.pos.barcode_products.mass_print_barcode'),
            ]);
        }
    }
}
