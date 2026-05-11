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
        return $this->withDisplayMetadata(parent::getTopSellingProductsByRevenue($limit));
    }

    public function getTopSellingProductsByQuantity($limit = null): Collection
    {
        return $this->withDisplayMetadata(parent::getTopSellingProductsByQuantity($limit));
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
