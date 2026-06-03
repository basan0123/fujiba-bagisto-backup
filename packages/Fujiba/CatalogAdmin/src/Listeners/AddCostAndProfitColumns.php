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
     * Join lifetime units sold per product. We aggregate in a subquery (grouped
     * by product_id) and left-join it, rather than joining order_items directly,
     * so the grid's own GROUP BY product_flat.product_id can't fan out the
     * order rows or distort the cost/inventory aggregates.
     *
     * Sold qty mirrors Bagisto's own reporting: SUM(qty_invoiced - qty_refunded),
     * counting only parent rows (parent_id IS NULL) so configurable parent+child
     * pairs are not double-counted.
     */
    public function joinSoldQty(DataGrid $datagrid): void
    {
        $qb = $datagrid->getQueryBuilder();
        if (! $qb) {
            return;
        }

        $prefix = DB::getTablePrefix();

        $qb->leftJoin(
            DB::raw(
                '(SELECT product_id, SUM(qty_invoiced - qty_refunded) AS total_sold '
                .'FROM '.$prefix.'order_items '
                .'WHERE parent_id IS NULL '
                .'GROUP BY product_id) AS fujiba_sold'
            ),
            'fujiba_sold.product_id', '=', 'product_flat.product_id'
        )->addSelect(DB::raw('COALESCE(MAX(fujiba_sold.total_sold), 0) as total_sold'));
    }

    /**
     * Apply the quick-filter chips from the product view-nav. Each chip links
     * to the product index with a single URL param; the Vue datagrid forwards
     * all URL params to the backend, and we read them here on
     * `process_request.filters.after` (after core filters, before pagination).
     *
     *   stock=in            quantity > 0
     *   stock=out           quantity <= 0 (or no inventory rows)
     *   product_status=1|0  product_flat.status
     *   missing_cost=1      cost is unset or <= 0
     *
     * Stock and missing_cost act on grouped aggregates, so they use HAVING with
     * the same expressions the grid already selects (quantity, cost).
     */
    public function applyChipFilters(DataGrid $datagrid): void
    {
        $qb = $datagrid->getQueryBuilder();
        if (! $qb) {
            return;
        }

        $prefix = DB::getTablePrefix();

        $stock = request('stock');
        if ($stock === 'in') {
            $qb->havingRaw('SUM(DISTINCT '.$prefix.'product_inventories.qty) > 0');
        } elseif ($stock === 'out') {
            $qb->havingRaw('COALESCE(SUM(DISTINCT '.$prefix.'product_inventories.qty), 0) <= 0');
        }

        $status = request('product_status');
        if ($status === '1' || $status === '0') {
            $qb->where('product_flat.status', (int) $status);
        }

        if (request('missing_cost') === '1') {
            $costAttributeId = $this->resolveCostAttributeId();
            if ($costAttributeId) {
                // cost column is MAX(fujiba_pav_cost.float_value); unset or zero
                // both count as "missing" for the dashboard cost work.
                $qb->havingRaw('COALESCE(MAX(fujiba_pav_cost.float_value), 0) <= 0');
            }
        }
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

        // Lifetime units sold. Sortable so admins can find best/worst sellers;
        // the underlying total_sold select comes from joinSoldQty().
        $datagrid->addColumn([
            'index'      => 'total_sold',
            'label'      => trans('catalog-admin::app.products.datagrid.total_sold'),
            'type'       => 'integer',
            'sortable'   => true,
            'filterable' => false,
            'closure'    => function ($row) {
                return (int) ($row->total_sold ?? 0);
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
