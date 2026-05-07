<?php

namespace Fujiba\CatalogAdmin\Listeners;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class AddCostAndProfitColumns
{
    /**
     * Join the EAV `cost` attribute onto the product datagrid query so the
     * grid can display unit cost and per-unit profit alongside price. Cost
     * is global at Fujiba (attribute is_global), so no channel/locale scope
     * is needed on the join. Listens to `query_builder.prepare.after` which
     * fires after setQueryBuilder() but before filters/pagination apply.
     */
    public function joinCost(DataGrid $datagrid): void
    {
        $qb = $datagrid->getQueryBuilder();
        if (! $qb) {
            return;
        }

        $costAttributeId = $this->resolveCostAttributeId();
        if (! $costAttributeId) {
            return;
        }

        $qb->leftJoin('product_attribute_values as fujiba_pav_cost', function ($join) use ($costAttributeId) {
            $join->on('fujiba_pav_cost.product_id', '=', 'product_flat.product_id')
                ->where('fujiba_pav_cost.attribute_id', $costAttributeId);
        })->addSelect(DB::raw('MAX(fujiba_pav_cost.float_value) as cost'));
    }

    /**
     * Register Cost and Profit columns. Profit is per-unit (price - cost).
     * When cost is missing or zero we render an em-dash instead of price so
     * an unset cost is not mistaken for 100% margin.
     */
    public function addColumns(DataGrid $datagrid): void
    {
        // Register profit BEFORE cost: DataGrid::formatRecords runs column
        // closures in registration order and mutates $row->{index} with the
        // closure result. Profit must read the raw numeric $row->cost, so it
        // has to execute before the cost closure rewrites that field.
        $datagrid->addColumn([
            'index'      => 'profit',
            'label'      => trans('catalog-admin::app.products.datagrid.profit'),
            'type'       => 'decimal',
            'sortable'   => false,
            'filterable' => false,
            'closure'    => function ($row) {
                $price = isset($row->price) ? (float) $row->price : null;
                $cost  = isset($row->cost) ? (float) $row->cost : null;

                if ($price === null || $cost === null || $cost <= 0.0) {
                    return '—';
                }

                $profit = $price - $cost;
                $margin = $price > 0 ? round(($profit / $price) * 100, 1) : 0;

                return core()->formatBasePrice($profit) . ' (' . $margin . '%)';
            },
        ]);

        $datagrid->addColumn([
            'index'      => 'cost',
            'label'      => trans('catalog-admin::app.products.datagrid.cost'),
            'type'       => 'decimal',
            'sortable'   => true,
            'filterable' => false,
            'closure'    => function ($row) {
                if (! isset($row->cost) || $row->cost === null) {
                    return '—';
                }
                return core()->formatBasePrice((float) $row->cost);
            },
        ]);
    }

    protected function resolveCostAttributeId(): ?int
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached ?: null;
        }

        $row = DB::table('attributes')->where('code', 'cost')->first(['id']);
        $cached = $row ? (int) $row->id : 0;

        return $cached ?: null;
    }
}
