<?php

namespace Fujiba\PwaFrontend\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Webkul\Product\ProductImage;
use Webkul\Product\Repositories\ProductRepository;

/**
 * Lightweight fuzzy product search for the PWA storefront.
 *
 * Strategy:
 *   1. Try an exact substring match (fast). If we get ≥ $minHits, return.
 *   2. Otherwise pull a broader candidate pool (channel-scoped) and rank it
 *      with a Levenshtein-distance score against the query.
 *
 * This deliberately runs on the local DB — fine for catalogs up to a few
 * thousand SKUs. Anything bigger should switch to an elasticsearch index.
 */
class SearchController extends Controller
{
    public function __construct(protected ProductRepository $products) {}

    public function __invoke(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));
        $limit = (int) $request->query('limit', 24);
        $limit = max(1, min($limit, 50));

        if ($term === '' || mb_strlen($term) < 2) {
            return response()->json(['data' => [], 'meta' => ['total' => 0, 'fuzzy' => false]]);
        }

        $channelId = core()->getCurrentChannel()->id;

        $exactIds = $this->exactMatchIds($term, $channelId, $limit);
        $usedFuzzy = false;
        $rankedIds = $exactIds;

        if (count($exactIds) < 3) {
            $candidates = $this->candidatePool($term, $channelId, 200);
            $ranked = $this->rankByDistance($term, $candidates);
            // Merge: exact hits first (preserve order), then fuzzy hits not
            // already in the exact list.
            $merged = $exactIds;
            foreach ($ranked as $row) {
                if (! in_array($row['id'], $merged, true)) {
                    $merged[] = $row['id'];
                }
                if (count($merged) >= $limit) break;
            }
            $rankedIds = array_slice($merged, 0, $limit);
            $usedFuzzy = count($exactIds) < count($rankedIds);
        }

        if (empty($rankedIds)) {
            return response()->json(['data' => [], 'meta' => ['total' => 0, 'fuzzy' => $usedFuzzy]]);
        }

        // products table has no `status` column — that filter is enforced
        // upstream when building the candidate pool from product_flat.
        $products = $this->products->getModel()
            ->newQuery()
            ->whereIn('id', $rankedIds)
            ->get()
            ->sortBy(fn ($p) => array_search($p->id, $rankedIds, true))
            ->values();

        $imageHelper = app(ProductImage::class);

        $data = $products->map(function ($product) use ($imageHelper) {
            try {
                $images = $imageHelper->getProductBaseImage($product);
            } catch (\Throwable $e) {
                $images = [];
            }
            $price = (float) $product->price;
            return [
                'id'              => $product->id,
                'sku'             => $product->sku,
                'name'            => $product->name ?? $product->sku,
                'url_key'         => $product->url_key,
                'price'           => $price,
                'formatted_price' => core()->formatPrice($price),
                'in_stock'        => true,
                'base_image'      => [
                    'small_image_url'  => $images['small_image_url']  ?? null,
                    'medium_image_url' => $images['medium_image_url'] ?? null,
                ],
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => count($rankedIds),
                'fuzzy' => $usedFuzzy,
            ],
        ]);
    }

    /**
     * Channel-scoped exact-substring product IDs, ordered by name.
     * Tries the active locale, falls back to any available locale row so
     * stores that haven't translated every product still surface results.
     */
    private function exactMatchIds(string $term, int $channelId, int $limit): array
    {
        $channelCode = core()->getCurrentChannel()->code;
        $base = fn () => DB::table('product_flat')
            ->where('channel', '=', $channelCode)
            ->where('status', 1)
            ->where('visible_individually', 1)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%')
                  ->orWhere('sku', 'like', '%' . $term . '%');
            })
            ->orderBy('name')
            ->limit($limit);

        $ids = (clone $base())
            ->where('locale', '=', app()->getLocale())
            ->pluck('product_id')
            ->all();

        if (count($ids) >= 3) {
            return $ids;
        }

        // Fall back to any locale row, dedup against what we already have.
        $extra = $base()
            ->whereNotIn('product_id', $ids)
            ->pluck('product_id')
            ->all();

        return array_values(array_unique(array_merge($ids, $extra)));
    }

    /**
     * Pull a broader set so we can rank fuzzy candidates client-side.
     * Uses one row per product (any locale) — the ranker only needs the name
     * for distance scoring.
     */
    private function candidatePool(string $term, int $channelId, int $cap): array
    {
        $channelCode = core()->getCurrentChannel()->code;
        return DB::table('product_flat')
            ->select('product_id', 'name', 'sku')
            ->where('channel', '=', $channelCode)
            ->where('status', 1)
            ->where('visible_individually', 1)
            ->groupBy('product_id', 'name', 'sku')
            ->limit($cap)
            ->get()
            ->map(fn ($r) => ['id' => (int) $r->product_id, 'name' => (string) $r->name, 'sku' => (string) $r->sku])
            ->all();
    }

    /**
     * Rank candidates by similarity against the query. Tries:
     *   1. Substring on name / sku.
     *   2. Substring on punctuation-stripped name / sku ("FT3" → "FT-3-A").
     *   3. Levenshtein against each word AND against the stripped full name.
     *
     * Returns rows ordered by best match first, tossing anything obviously off.
     */
    private function rankByDistance(string $term, array $rows): array
    {
        $term = mb_strtolower($term);
        $tlen = mb_strlen($term);

        // Punctuation-stripped query. Keeps letters, digits, CJK.
        $tStripped = preg_replace('/[^\p{L}\p{N}]+/u', '', $term);
        $tsLen = mb_strlen($tStripped ?: '');

        $scored = [];

        foreach ($rows as $row) {
            $name = mb_strtolower($row['name']);
            $sku  = mb_strtolower($row['sku']);
            $nameStripped = preg_replace('/[^\p{L}\p{N}]+/u', '', $name);
            $skuStripped  = preg_replace('/[^\p{L}\p{N}]+/u', '', $sku);

            // 1. Direct substring (after the SQL LIKE already missed,
            // these are normally case/space variations the SQL skipped).
            if ($name !== '' && mb_strpos($name, $term) !== false) { $scored[] = ['id' => $row['id'], 'score' => 0]; continue; }
            if ($sku  !== '' && mb_strpos($sku,  $term) !== false) { $scored[] = ['id' => $row['id'], 'score' => 1]; continue; }

            // 2. Substring on stripped versions ("ft3" matches "FT-3-A").
            if ($tStripped !== '' && $nameStripped !== '' && mb_strpos($nameStripped, $tStripped) !== false) {
                $scored[] = ['id' => $row['id'], 'score' => 2];
                continue;
            }
            if ($tStripped !== '' && $skuStripped !== '' && mb_strpos($skuStripped, $tStripped) !== false) {
                $scored[] = ['id' => $row['id'], 'score' => 3];
                continue;
            }

            // 3. Levenshtein. Compare against each name-word and against the
            // full stripped name (handles "kimino" vs "kimono" etc.).
            $best = PHP_INT_MAX;
            $candidates = preg_split('/\s+/u', $name) ?: [];
            if ($nameStripped) $candidates[] = $nameStripped;

            foreach ($candidates as $word) {
                if ($word === '') continue;
                $wlen = mb_strlen($word);
                if (abs($wlen - $tlen) > 3) continue;
                $d = @levenshtein(mb_substr($word, 0, 64), mb_substr($term, 0, 64));
                if ($d >= 0 && $d < $best) $best = $d;
            }

            // Tolerance scales with query length: 1 for ≤4 chars, 2 for ≤8, 3 otherwise.
            $allow = $tlen <= 4 ? 1 : ($tlen <= 8 ? 2 : 3);
            if ($best <= $allow) {
                $scored[] = ['id' => $row['id'], 'score' => 10 + $best];
            }
        }

        usort($scored, fn ($a, $b) => $a['score'] <=> $b['score']);
        return $scored;
    }
}
