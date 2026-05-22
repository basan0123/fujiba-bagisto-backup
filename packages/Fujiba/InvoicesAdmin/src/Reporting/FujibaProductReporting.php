<?php

namespace Fujiba\InvoicesAdmin\Reporting;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Helpers\Reporting\Product as CoreProductReporting;

/**
 * Top-selling reporting overrides.
 *
 * The base Webkul helper returns `order_items.name` as the product display
 * name. On Fujiba (and the ChatPion-imported catalog) `order_items.name` is
 * populated with the SKU rather than the localized title — so the Reporting
 * dashboard's "売上別最売商品" / "数量別最売商品" panels render SKUs like
 * `MMFOOD-2444` instead of the actual product name. We re-resolve each row
 * against `product_flat` (preferring the current admin locale) and tack on
 * the first product image so the blade can show a thumbnail.
 */
class FujibaProductReporting extends CoreProductReporting
{
    public function getTopSellingProductsByRevenue($limit = null): Collection
    {
        return $this->withDisplayMetadata(
            $this->topSelling($limit, 'SUM(base_total_invoiced - base_amount_refunded)', 'revenue')
        );
    }

    public function getTopSellingProductsByQuantity($limit = null): Collection
    {
        return $this->withOperationsMetadata(
            $this->withDisplayMetadata(
                $this->topSelling($limit, 'SUM(qty_invoiced - qty_refunded)', 'total_qty_ordered')
            )
        );
    }

    /**
     * Reimplements the core top-selling query so the report window honors the
     * Orders/Payments mode toggle ([[feedback_paid_at_dual_writer_drift]]).
     *
     * The core helper hardcodes `order_items.created_at`; in Payments mode we
     * must instead bound on `orders.paid_at` (paid_at lives on orders, not
     * order_items). getDateColumn() returns 'paid_at' or 'created_at'; for
     * 'created_at' we keep filtering on order_items (matches core behavior so
     * Orders mode is unchanged), for 'paid_at' we filter the joined orders row.
     */
    protected function topSelling(?int $limit, string $aggregateExpr, string $alias): Collection
    {
        $dateColumn = $this->getDateColumn();
        $dateScoped = $dateColumn === 'paid_at' ? 'orders.paid_at' : 'order_items.created_at';

        $items = $this->orderItemRepository
            ->resetModel()
            ->with(['product', 'product.attribute_family', 'product.attribute_values', 'product.images'])
            ->leftJoin('orders', 'order_items.order_id', '=', 'orders.id')
            ->addSelect('*', DB::raw("{$aggregateExpr} as {$alias}"))
            ->whereNull('parent_id')
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween($dateScoped, [$this->startDate, $this->endDate])
            ->having(DB::raw($aggregateExpr), '>', 0)
            ->groupBy('product_id')
            ->orderBy($alias, 'DESC')
            ->limit($limit)
            ->get();

        return $items->map(function ($item) use ($alias) {
            $row = [
                'id' => $item->product_id,
                'name' => $item->name,
                'price' => $item->product?->price,
                'formatted_price' => core()->formatBasePrice($item->price),
                $alias => $item->{$alias},
                'images' => $item->product?->images,
            ];

            // The revenue flow also expects a formatted_revenue (the core
            // helper adds it for the graph view; the table view reads it too).
            if ($alias === 'revenue') {
                $row['formatted_revenue'] = core()->formatBasePrice($item->{$alias});
            }

            return $row;
        });
    }

    /**
     * Adds waste (warehouse + POS), current stock (channel-scoped), and a
     * forward 60-day demand forecast scaled from the current report window.
     * Used by the Reporting > Products > top-selling-by-quantity table view.
     */
    protected function withOperationsMetadata(Collection $items): Collection
    {
        $productIds = $items->pluck('id')->filter()->unique()->values();

        if ($productIds->isEmpty()) {
            return $items;
        }

        // Window-scoped waste, channel-scoped where the table has channel_id.
        $warehouseWaste = DB::table('waste_records')
            ->whereIn('product_id', $productIds)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->when(! empty($this->channelIds), fn ($q) => $q->whereIn('channel_id', $this->channelIds))
            ->groupBy('product_id')
            ->pluck(DB::raw('SUM(quantity)'), 'product_id');

        $posWaste = DB::table('pos_waste_entries')
            ->whereIn('product_id', $productIds)
            ->whereBetween('wasted_at', [$this->startDate->toDateString(), $this->endDate->toDateString()])
            ->groupBy('product_id')
            ->pluck(DB::raw('SUM(quantity)'), 'product_id');

        // Current stock from product_inventory_indices, scoped to the report
        // channel filter when set; otherwise pick the max across channels
        // (the indices are per-channel mirrors of the same inventory pool).
        $stockQuery = DB::table('product_inventory_indices')
            ->whereIn('product_id', $productIds);

        if (! empty($this->channelIds)) {
            $stockQuery->whereIn('channel_id', $this->channelIds)
                ->groupBy('product_id')
                ->select('product_id', DB::raw('SUM(qty) as qty'));
        } else {
            $stockQuery->groupBy('product_id')
                ->select('product_id', DB::raw('MAX(qty) as qty'));
        }

        $stock = $stockQuery->pluck('qty', 'product_id');

        // Forecast: scale the report window's invoiced qty to 60 days.
        $windowDays = max(1, $this->startDate->diffInDays($this->endDate) + 1);
        $forecastScale = 60 / $windowDays;

        return $items->map(function ($item) use ($warehouseWaste, $posWaste, $stock, $forecastScale) {
            $row = is_array($item) ? $item : (array) $item;
            $id = $row['id'] ?? null;

            $waste = (float) ($warehouseWaste[$id] ?? 0) + (float) ($posWaste[$id] ?? 0);
            $row['waste_qty'] = $this->formatQty($waste);

            $row['current_stock'] = (int) ($stock[$id] ?? 0);

            $sold = (float) ($row['total_qty_ordered'] ?? 0);
            $row['forecast_next_2m'] = (int) round($sold * $forecastScale);

            return $row;
        });
    }

    protected function formatQty(float $qty): string
    {
        // Waste tables store decimal(12,4); show integer when no fraction.
        return floor($qty) == $qty ? (string) (int) $qty : rtrim(rtrim(number_format($qty, 4, '.', ''), '0'), '.');
    }

    /**
     * Replace `name` with the localized product_flat name and add a first-image
     * thumbnail URL. Falls back to the SKU only when no flat name exists.
     */
    protected function withDisplayMetadata(Collection $items): Collection
    {
        $productIds = $items->pluck('id')->filter()->unique()->values();

        if ($productIds->isEmpty()) {
            return $items;
        }

        $locale = core()->getRequestedLocaleCode() ?: app()->getLocale();

        $names = DB::table('product_flat')
            ->whereIn('product_id', $productIds)
            ->orderByRaw('CASE WHEN locale = ? THEN 0 ELSE 1 END', [$locale])
            ->orderBy('id')
            ->get(['product_id', 'name', 'locale'])
            ->keyBy('product_id');

        $images = DB::table('product_images')
            ->whereIn('product_id', $productIds)
            ->orderBy('id')
            ->get(['product_id', 'path'])
            ->groupBy('product_id')
            ->map(fn ($rows) => $rows->first());

        return $items->map(function ($item) use ($names, $images) {
            $row = is_array($item) ? $item : (array) $item;

            $flat = $names->get($row['id'] ?? null);

            if ($flat && ! empty($flat->name)) {
                $row['name'] = $flat->name;
            }

            $imageRow = $images->get($row['id'] ?? null);
            $row['thumbnail_url'] = $this->resolveImageUrl($imageRow->path ?? null);

            return $row;
        });
    }

    protected function resolveImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return preg_match('#^https?://#', $path) ? $path : Storage::url($path);
    }
}
