<?php

namespace Fujiba\InvoicesAdmin\Reporting;

use Webkul\Admin\Helpers\Reporting as CoreReporting;

/**
 * Decorates the core Reporting helper so the "top selling products by
 * quantity" detail page (admin/reporting/products/view?type=...) renders
 * three additional operations columns: waste, current stock, and a
 * forward 60-day demand forecast.
 *
 * The row data is enriched upstream in {@see FujibaProductReporting}; this
 * class just exposes the matching column schema so the generic table
 * renderer in resources/views/reporting/view.blade.php picks them up.
 */
class FujibaReporting extends CoreReporting
{
    public function getTopSellingProductsByQuantity($type = 'graph'): array
    {
        if ($type !== 'table') {
            return parent::getTopSellingProductsByQuantity($type);
        }

        $records = $this->productReporting->getTopSellingProductsByQuantity();

        return [
            'columns' => [
                [
                    'key' => 'thumbnail_url',
                    'label' => '',
                    'type' => 'image',
                ], [
                    'key' => 'id',
                    'label' => trans('admin::app.reporting.products.index.id'),
                ], [
                    'key' => 'name',
                    'label' => trans('admin::app.reporting.products.index.name'),
                ], [
                    'key' => 'total_qty_ordered',
                    'label' => trans('admin::app.reporting.products.index.quantities'),
                ], [
                    'key' => 'waste_qty',
                    'label' => trans('invoices-admin::app.reporting.products.waste'),
                ], [
                    'key' => 'current_stock',
                    'label' => trans('invoices-admin::app.reporting.products.current-stock'),
                ], [
                    'key' => 'forecast_next_2m',
                    'label' => trans('invoices-admin::app.reporting.products.forecast-next-2m'),
                ],
            ],

            'records' => $records,
        ];
    }
}
