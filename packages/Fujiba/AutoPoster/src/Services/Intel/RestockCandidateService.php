<?php

namespace Fujiba\AutoPoster\Services\Intel;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Restock shortlist: products that are SOLD OUT at Fujiba (disabled, or
 * available = stock − reserved <= 0) and that competitors are visibly selling.
 *
 * Evidence = fi_competitor_product_map rows that are eye-verified decisions
 * (candidate_rank IS NULL, verified_side_by_side = 1) with match_confidence
 * exact|probable|possible. Raw matcher candidates (candidate_rank set) are
 * never used. their_code (supplier code) is never read.
 *
 * Reads Bagisto tables only (product_flat, product_inventories,
 * product_ordered_inventories, orders, order_items, product_stock_audits);
 * writes nothing — the command persists the result into fi_restock_candidates.
 */
class RestockCandidateService
{
    public const CONFIDENCE_WEIGHT = ['exact' => 3, 'probable' => 2, 'possible' => 1];

    public const STOCK_DATE_SOURCE = 'proxy: product_stock_audits (logging since 2026-04-30); product_inventories has no timestamps';

    /**
     * evidence_score = weight(best confidence) × (1 + 0.5 × (distinct_competitors − 1))
     *                  + 0.25 × times_seen + 0.5 if a competitor price was shown.
     */
    public static function evidenceScore(string $bestConfidence, int $distinctCompetitors, int $timesSeen, bool $priceShown): float
    {
        $w = self::CONFIDENCE_WEIGHT[$bestConfidence] ?? 0;

        return round($w * (1 + 0.5 * max(0, $distinctCompetitors - 1)) + 0.25 * $timesSeen + ($priceShown ? 0.5 : 0), 3);
    }

    /** Sort: evidence_score desc, sold_qty_12m desc, product_id asc. */
    public static function sort(array $rows): array
    {
        usort($rows, fn ($a, $b) => [$b['evidence_score'], $b['sold_qty_12m'], $a['product_id']]
            <=> [$a['evidence_score'], $a['sold_qty_12m'], $b['product_id']]);
        foreach ($rows as $i => &$r) {
            $r['rank'] = $i + 1;
        }

        return $rows;
    }

    /**
     * @return array{rows: list<array>, evidence_products: int, in_stock_excluded: list<array>}
     */
    public function compute(): array
    {
        $ev = DB::table('fi_competitor_product_map as m')
            ->join('fi_competitors as c', 'c.id', '=', 'm.competitor_id')
            ->leftJoin('fi_competitor_posts as p', 'p.id', '=', 'm.competitor_post_id')
            ->whereNull('m.candidate_rank')
            ->where('m.verified_side_by_side', 1)
            ->whereIn('m.match_confidence', array_keys(self::CONFIDENCE_WEIGHT))
            ->whereNotNull('m.product_id')
            ->orderBy('m.id')
            ->get([
                'm.id', 'm.product_id', 'm.competitor_id', 'c.handle', 'c.platform', 'm.competitor_post_id',
                'm.region', 'm.match_method', 'm.match_confidence', 'm.their_price', 'm.their_price_note',
                'p.external_id', 'p.price_shown', 'p.price_min', 'p.price_max',
            ])
            ->groupBy('product_id');

        $ids = $ev->keys()->map(fn ($id) => (int) $id)->all();
        if (! $ids) {
            return ['rows' => [], 'evidence_products' => 0, 'in_stock_excluded' => []];
        }

        $flat = $this->flat($ids);
        $stock = DB::table('product_inventories')->whereIn('product_id', $ids)
            ->groupBy('product_id')->selectRaw('product_id, SUM(qty) q')->pluck('q', 'product_id');
        $reserved = DB::table('product_ordered_inventories')->whereIn('product_id', $ids)
            ->groupBy('product_id')->selectRaw('product_id, SUM(qty) q')->pluck('q', 'product_id');
        $sales = $this->sales($ids);
        $audit = $this->audit($ids);

        $rows = [];
        $inStock = [];
        foreach ($ev as $pid => $list) {
            $pid = (int) $pid;
            $f = $flat[$pid] ?? null;
            $enabled = (int) ($f->status ?? 0) === 1;
            $stockQty = (int) ($stock[$pid] ?? 0);
            $resQty = (int) ($reserved[$pid] ?? 0);
            $avail = $stockQty - $resQty;

            if ($enabled && $avail > 0) {
                $inStock[] = ['product_id' => $pid, 'name' => PublicCode::mask($f->name ?? ''), 'available_qty' => $avail];

                continue;
            }

            $handles = $list->groupBy('handle')->map->count()->sortKeys()->sortDesc();
            $best = $list->sortByDesc(fn ($r) => self::CONFIDENCE_WEIGHT[$r->match_confidence])->first()->match_confidence;

            $prices = [];
            $notes = [];
            foreach ($list as $r) {
                if ($r->their_price !== null) {
                    $prices[] = (float) $r->their_price;
                    $prices[] = (float) $r->their_price;
                    $notes[] = $r->their_price_note ?: ('¥'.(int) $r->their_price);
                } elseif ($r->price_shown !== null && $r->price_min !== null) {
                    $prices[] = (float) $r->price_min;
                    $prices[] = (float) ($r->price_max ?? $r->price_min);
                    $notes[] = 'post shows '.$r->price_shown;
                }
            }
            $priceShown = (bool) $prices;
            $distinct = $list->pluck('competitor_id')->unique()->count();
            $seen = $list->pluck('competitor_post_id')->unique()->count();
            $s = $sales[$pid] ?? null;
            $a = $audit[$pid] ?? null;

            $rows[] = [
                'product_id'             => $pid,
                'sku'                    => PublicCode::mask($f->sku ?? ''),
                'public_name'            => PublicCode::mask($f->name ?? ''),
                'channel'                => $f->channel ?? 'default',
                'enabled'                => $enabled,
                'stock_qty'              => $stockQty,
                'reserved_qty'           => $resQty,
                'available_qty'          => $avail,
                'sold_out_reason'        => ! $enabled && $avail <= 0 ? 'disabled+no_stock' : (! $enabled ? 'disabled' : 'no_stock'),
                'competitors'            => $handles->keys()->implode(','),
                'distinct_competitors'   => $distinct,
                'times_seen'             => $seen,
                'best_confidence'        => $best,
                'their_price_min'        => $priceShown ? min($prices) : null,
                'their_price_max'        => $priceShown ? max($prices) : null,
                'their_price_note'       => $priceShown ? mb_substr(PublicCode::mask(implode('; ', array_unique($notes))), 0, 255) : null,
                'our_price'              => isset($f->price) ? round((float) $f->price, 2) : null,
                'last_sold_at'           => $s->last_sold_at ?? null,
                'sold_qty_all_time'      => (int) ($s->all_time ?? 0),
                'sold_qty_12m'           => (int) ($s->m12 ?? 0),
                'sold_qty_12m_completed' => (int) ($s->m12_completed ?? 0),
                'last_stock_change_at'   => $a->last_change ?? null,
                'last_restock_at'        => $a->last_restock ?? null,
                'stock_date_source'      => self::STOCK_DATE_SOURCE,
                'evidence_score'         => self::evidenceScore($best, $distinct, $seen, $priceShown),
                'evidence'               => $list->map(fn ($r) => [
                    'map_id'     => (int) $r->id,
                    'handle'     => $r->handle,
                    'platform'   => $r->platform,
                    'post_id'    => (int) $r->competitor_post_id,
                    'post_ref'   => $r->external_id,
                    'region'     => $r->region,
                    'method'     => $r->match_method,
                    'confidence' => $r->match_confidence,
                ])->values()->all(),
            ];
        }

        return ['rows' => self::sort($rows), 'evidence_products' => count($ids), 'in_stock_excluded' => $inStock];
    }

    /** One product_flat row per product: its channel's default locale (default channel → ja). */
    private function flat(array $ids): array
    {
        // aliases matter: both columns are called "code" and would collide in pluck()
        $defaultLocale = DB::table('channels as c')->join('locales as l', 'l.id', '=', 'c.default_locale_id')
            ->selectRaw('c.code AS channel_code, l.code AS locale_code')->pluck('locale_code', 'channel_code')->all();

        // prefer the default channel, then the channel's own default locale
        $score = fn ($r) => ($r->channel === 'default' ? 2 : 0) + (($defaultLocale[$r->channel] ?? 'ja') === $r->locale ? 1 : 0);

        $out = [];
        foreach (DB::table('product_flat')->whereIn('product_id', $ids)->orderBy('id')
            ->get(['product_id', 'sku', 'name', 'status', 'price', 'channel', 'locale']) as $r) {
            if (! isset($out[$r->product_id]) || $score($r) > $score($out[$r->product_id])) {
                $out[$r->product_id] = $r;
            }
        }

        return $out;
    }

    /**
     * Sales per product from non-canceled orders, net of line-level
     * cancellations/refunds. 12 months = rolling window back from now.
     */
    private function sales(array $ids): array
    {
        $since = now()->subMonths(12)->toDateTimeString();
        $net = 'GREATEST(i.qty_ordered - COALESCE(i.qty_canceled,0) - COALESCE(i.qty_refunded,0), 0)';

        return DB::table('order_items as i')
            ->join('orders as o', 'o.id', '=', 'i.order_id')
            ->whereIn('i.product_id', $ids)
            ->where('o.status', '<>', 'canceled')
            ->whereRaw("$net > 0")
            ->groupBy('i.product_id')
            ->selectRaw("i.product_id, MAX(o.created_at) last_sold_at, SUM($net) all_time,
                SUM(CASE WHEN o.created_at >= ? THEN $net ELSE 0 END) m12,
                SUM(CASE WHEN o.created_at >= ? AND o.status = 'completed' THEN $net ELSE 0 END) m12_completed", [$since, $since])
            ->get()->keyBy('product_id')->all();
    }

    private function audit(array $ids): array
    {
        if (! Schema::hasTable('product_stock_audits')) {
            return [];
        }

        return DB::table('product_stock_audits')->whereIn('product_id', $ids)
            ->groupBy('product_id')
            ->selectRaw('product_id, MAX(created_at) last_change, MAX(CASE WHEN delta > 0 THEN created_at END) last_restock')
            ->get()->keyBy('product_id')->all();
    }
}
