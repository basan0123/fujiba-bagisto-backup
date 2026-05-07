<?php

namespace Fujiba\Dashboard\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Channel-agnostic inventory value queries.
 *
 * The same product table holds two channels (kimono channel_id=1, mmfood
 * channel_id=2). Cost and price live in EAV (attribute_id=12 cost,
 * attribute_id=11 price), both stored in float_value, both globally scoped
 * (channel/locale = NULL in this tenant).
 *
 * Each per-channel total uses INNER JOIN product_channels to scope.
 * All-company total uses INNER JOIN (SELECT DISTINCT product_id FROM
 * product_channels) to exclude orphan products consistently.
 *
 * > 0 filter on cost (W_C), retail (W_P), margin (W_C AND W_P).
 *
 * Six separate queries (3 per scope × 2 scopes) — kept un-folded for
 * readability per session decision.
 */
class InventoryValueRepository
{
    private const ATTR_COST  = 12;
    private const ATTR_PRICE = 11;

    private const CACHE_TTL_SECONDS = 300;

    /**
     * Per-channel totals (cost, retail, margin) plus three SKU counts —
     * one per total since each total has different "priced" semantics.
     */
    public function getChannelTotals(int $channelId): array
    {
        return Cache::remember(
            "fujiba.dashboard.inventory_value.channel.{$channelId}.v2",
            self::CACHE_TTL_SECONDS,
            fn () => [
                'cost'   => $this->fetchChannelCostTotal($channelId),
                'retail' => $this->fetchChannelRetailTotal($channelId),
                'margin' => $this->fetchChannelMarginTotal($channelId),
            ],
        );
    }

    /**
     * Tenant-wide totals (any product with at least one product_channels row).
     */
    public function getAllCompanyTotals(): array
    {
        return Cache::remember(
            'fujiba.dashboard.inventory_value.all.v2',
            self::CACHE_TTL_SECONDS,
            fn () => [
                'cost'   => $this->fetchAllCostTotal(),
                'retail' => $this->fetchAllRetailTotal(),
                'margin' => $this->fetchAllMarginTotal(),
            ],
        );
    }

    public function getMissingCostCount(int $channelId): int
    {
        return Cache::remember(
            "fujiba.dashboard.inventory_value.missing.channel.{$channelId}.v2",
            self::CACHE_TTL_SECONDS,
            fn () => $this->fetchMissingCostCount($channelId),
        );
    }

    public function getMissingCostCountAllChannels(): int
    {
        return Cache::remember(
            'fujiba.dashboard.inventory_value.missing.all.v2',
            self::CACHE_TTL_SECONDS,
            fn () => $this->fetchMissingCostCountAllChannels(),
        );
    }

    /**
     * Sum of inventory qty across all SKUs.
     *  - $channelId int  → scoped via product_channels JOIN to that channel.
     *  - $channelId null → all channels (any product with at least one
     *    product_channels row, mirroring getAllCompanyTotals).
     */
    public function getTotalStockQuantity(?int $channelId): int
    {
        $cacheKey = $channelId === null
            ? 'fujiba.dashboard.total_stock.all.v1'
            : "fujiba.dashboard.total_stock.ch{$channelId}.v1";

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            fn () => $this->fetchTotalStockQuantity($channelId),
        );
    }

    public function getFetchedAt(): string
    {
        return Carbon::now()->toIso8601String();
    }

    public function forget(): void
    {
        // Forget all v2 keys for this widget. Channel-specific keys are not
        // enumerable without knowing channel ids, so we forget the static ones.
        Cache::forget('fujiba.dashboard.inventory_value.all.v2');
        Cache::forget('fujiba.dashboard.inventory_value.missing.all.v2');
        foreach ([1, 2] as $channelId) {
            Cache::forget("fujiba.dashboard.inventory_value.channel.{$channelId}.v2");
            Cache::forget("fujiba.dashboard.inventory_value.missing.channel.{$channelId}.v2");
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // Per-channel queries (left column scope)
    // ──────────────────────────────────────────────────────────────────

    private function fetchChannelCostTotal(int $channelId): array
    {
        $row = DB::selectOne(
            'SELECT
                COALESCE(SUM(per_product.qty_total * pav.float_value), 0) AS value,
                COUNT(DISTINCT per_product.product_id)                    AS sku_count
             FROM (
                SELECT pi.product_id, SUM(pi.qty) AS qty_total
                FROM product_inventories pi
                GROUP BY pi.product_id
             ) AS per_product
             INNER JOIN product_channels pc
                     ON pc.product_id = per_product.product_id
                    AND pc.channel_id = ?
             INNER JOIN product_attribute_values pav
                     ON pav.product_id   = per_product.product_id
                    AND pav.attribute_id = ?
                    AND pav.float_value IS NOT NULL
                    AND pav.float_value > 0',
            [$channelId, self::ATTR_COST]
        );

        return [
            'value'     => (float) ($row->value ?? 0),
            'sku_count' => (int)   ($row->sku_count ?? 0),
        ];
    }

    private function fetchChannelRetailTotal(int $channelId): array
    {
        $row = DB::selectOne(
            'SELECT
                COALESCE(SUM(per_product.qty_total * pav.float_value), 0) AS value,
                COUNT(DISTINCT per_product.product_id)                    AS sku_count
             FROM (
                SELECT pi.product_id, SUM(pi.qty) AS qty_total
                FROM product_inventories pi
                GROUP BY pi.product_id
             ) AS per_product
             INNER JOIN product_channels pc
                     ON pc.product_id = per_product.product_id
                    AND pc.channel_id = ?
             INNER JOIN product_attribute_values pav
                     ON pav.product_id   = per_product.product_id
                    AND pav.attribute_id = ?
                    AND pav.float_value IS NOT NULL
                    AND pav.float_value > 0',
            [$channelId, self::ATTR_PRICE]
        );

        return [
            'value'     => (float) ($row->value ?? 0),
            'sku_count' => (int)   ($row->sku_count ?? 0),
        ];
    }

    /**
     * Margin: only products with BOTH cost > 0 AND price > 0 contribute.
     * Per business rule, a product missing either attribute is excluded
     * from margin entirely (not partially counted).
     */
    private function fetchChannelMarginTotal(int $channelId): array
    {
        $row = DB::selectOne(
            'SELECT
                COALESCE(SUM(per_product.qty_total * (pav_price.float_value - pav_cost.float_value)), 0) AS value,
                COUNT(DISTINCT per_product.product_id)                                                   AS sku_count
             FROM (
                SELECT pi.product_id, SUM(pi.qty) AS qty_total
                FROM product_inventories pi
                GROUP BY pi.product_id
             ) AS per_product
             INNER JOIN product_channels pc
                     ON pc.product_id = per_product.product_id
                    AND pc.channel_id = ?
             INNER JOIN product_attribute_values pav_price
                     ON pav_price.product_id   = per_product.product_id
                    AND pav_price.attribute_id = ?
                    AND pav_price.float_value IS NOT NULL
                    AND pav_price.float_value > 0
             INNER JOIN product_attribute_values pav_cost
                     ON pav_cost.product_id   = per_product.product_id
                    AND pav_cost.attribute_id = ?
                    AND pav_cost.float_value IS NOT NULL
                    AND pav_cost.float_value > 0',
            [$channelId, self::ATTR_PRICE, self::ATTR_COST]
        );

        return [
            'value'     => (float) ($row->value ?? 0),
            'sku_count' => (int)   ($row->sku_count ?? 0),
        ];
    }

    // ──────────────────────────────────────────────────────────────────
    // All-company queries (right column scope)
    // ──────────────────────────────────────────────────────────────────

    private function fetchAllCostTotal(): array
    {
        $row = DB::selectOne(
            'SELECT
                COALESCE(SUM(per_product.qty_total * pav.float_value), 0) AS value,
                COUNT(DISTINCT per_product.product_id)                    AS sku_count
             FROM (
                SELECT pi.product_id, SUM(pi.qty) AS qty_total
                FROM product_inventories pi
                GROUP BY pi.product_id
             ) AS per_product
             INNER JOIN (
                SELECT DISTINCT product_id FROM product_channels
             ) AS pc ON pc.product_id = per_product.product_id
             INNER JOIN product_attribute_values pav
                     ON pav.product_id   = per_product.product_id
                    AND pav.attribute_id = ?
                    AND pav.float_value IS NOT NULL
                    AND pav.float_value > 0',
            [self::ATTR_COST]
        );

        return [
            'value'     => (float) ($row->value ?? 0),
            'sku_count' => (int)   ($row->sku_count ?? 0),
        ];
    }

    private function fetchAllRetailTotal(): array
    {
        $row = DB::selectOne(
            'SELECT
                COALESCE(SUM(per_product.qty_total * pav.float_value), 0) AS value,
                COUNT(DISTINCT per_product.product_id)                    AS sku_count
             FROM (
                SELECT pi.product_id, SUM(pi.qty) AS qty_total
                FROM product_inventories pi
                GROUP BY pi.product_id
             ) AS per_product
             INNER JOIN (
                SELECT DISTINCT product_id FROM product_channels
             ) AS pc ON pc.product_id = per_product.product_id
             INNER JOIN product_attribute_values pav
                     ON pav.product_id   = per_product.product_id
                    AND pav.attribute_id = ?
                    AND pav.float_value IS NOT NULL
                    AND pav.float_value > 0',
            [self::ATTR_PRICE]
        );

        return [
            'value'     => (float) ($row->value ?? 0),
            'sku_count' => (int)   ($row->sku_count ?? 0),
        ];
    }

    private function fetchAllMarginTotal(): array
    {
        $row = DB::selectOne(
            'SELECT
                COALESCE(SUM(per_product.qty_total * (pav_price.float_value - pav_cost.float_value)), 0) AS value,
                COUNT(DISTINCT per_product.product_id)                                                   AS sku_count
             FROM (
                SELECT pi.product_id, SUM(pi.qty) AS qty_total
                FROM product_inventories pi
                GROUP BY pi.product_id
             ) AS per_product
             INNER JOIN (
                SELECT DISTINCT product_id FROM product_channels
             ) AS pc ON pc.product_id = per_product.product_id
             INNER JOIN product_attribute_values pav_price
                     ON pav_price.product_id   = per_product.product_id
                    AND pav_price.attribute_id = ?
                    AND pav_price.float_value IS NOT NULL
                    AND pav_price.float_value > 0
             INNER JOIN product_attribute_values pav_cost
                     ON pav_cost.product_id   = per_product.product_id
                    AND pav_cost.attribute_id = ?
                    AND pav_cost.float_value IS NOT NULL
                    AND pav_cost.float_value > 0',
            [self::ATTR_PRICE, self::ATTR_COST]
        );

        return [
            'value'     => (float) ($row->value ?? 0),
            'sku_count' => (int)   ($row->sku_count ?? 0),
        ];
    }

    // ──────────────────────────────────────────────────────────────────
    // Missing-cost counts
    // ──────────────────────────────────────────────────────────────────

    private function fetchMissingCostCount(int $channelId): int
    {
        $row = DB::selectOne(
            'SELECT COUNT(DISTINCT p.id) AS missing_cost_count
             FROM products p
             INNER JOIN product_channels pc
                     ON pc.product_id = p.id
                    AND pc.channel_id = ?
             LEFT JOIN product_attribute_values pav
                    ON pav.product_id   = p.id
                   AND pav.attribute_id = ?
             WHERE pav.float_value IS NULL
                OR pav.float_value <= 0',
            [$channelId, self::ATTR_COST]
        );

        return (int) ($row->missing_cost_count ?? 0);
    }

    private function fetchTotalStockQuantity(?int $channelId): int
    {
        if ($channelId === null) {
            $row = DB::selectOne(
                'SELECT COALESCE(SUM(per_product.qty_total), 0) AS v
                 FROM (
                    SELECT pi.product_id, SUM(pi.qty) AS qty_total
                    FROM product_inventories pi
                    GROUP BY pi.product_id
                 ) AS per_product
                 INNER JOIN (
                    SELECT DISTINCT product_id FROM product_channels
                 ) AS pc ON pc.product_id = per_product.product_id'
            );

            return (int) ($row->v ?? 0);
        }

        $row = DB::selectOne(
            'SELECT COALESCE(SUM(per_product.qty_total), 0) AS v
             FROM (
                SELECT pi.product_id, SUM(pi.qty) AS qty_total
                FROM product_inventories pi
                GROUP BY pi.product_id
             ) AS per_product
             INNER JOIN product_channels pc
                     ON pc.product_id = per_product.product_id
                    AND pc.channel_id = ?',
            [$channelId]
        );

        return (int) ($row->v ?? 0);
    }

    private function fetchMissingCostCountAllChannels(): int
    {
        $row = DB::selectOne(
            'SELECT COUNT(DISTINCT p.id) AS missing_cost_count
             FROM products p
             INNER JOIN (
                SELECT DISTINCT product_id FROM product_channels
             ) AS pc ON pc.product_id = p.id
             LEFT JOIN product_attribute_values pav
                    ON pav.product_id   = p.id
                   AND pav.attribute_id = ?
             WHERE pav.float_value IS NULL
                OR pav.float_value <= 0',
            [self::ATTR_COST]
        );

        return (int) ($row->missing_cost_count ?? 0);
    }
}
