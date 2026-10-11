<?php

namespace Fujiba\AutoPoster\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * WS-I — loads competitor research (collected via TikTok oEmbed + owner
 * screenshots) from storage/app/intel/competitors/competitor_intel_processed.json
 * into the fi_competitor* / fi_hashtag_library / fi_product_* staging tables.
 *
 * Writes ONLY to fi_ tables. Bagisto product tables are read (SELECT) to
 * refresh our price / enabled / stock on map rows — never written.
 * Idempotent: upserts by natural keys; map rows for seeded posts are replaced.
 *
 * Run inside a tenant context:
 *   tenancy()->initialize('<tenant>');
 *   require '<path>/CompetitorIntelSeeder.php';
 *   (new \Fujiba\AutoPoster\Database\Seeders\CompetitorIntelSeeder)->run();
 */
class CompetitorIntelSeeder extends Seeder
{
    private const JSON_FIELDS = [
        'raw', 'hashtags', 'fetch_attempts', 'secondary_formats', 'props',
        'competitors_using', 'example_post_ids', 'source_competitors', 'value',
    ];

    public function __construct(private ?string $path = null)
    {
        $this->path ??= base_path('storage/app/intel/competitors/competitor_intel_processed.json');
    }

    public function run(): void
    {
        if (! tenancy()->initialized) {
            throw new RuntimeException('Initialize the tenant first.');
        }
        if (! is_file($this->path) || filesize($this->path) === 0) {
            throw new RuntimeException("Missing or empty data file: {$this->path}");
        }

        $data = json_decode(file_get_contents($this->path), true, 512, JSON_THROW_ON_ERROR);
        $now = now();
        $counts = [];

        DB::transaction(function () use ($data, $now, &$counts) {
            // competitors
            $compIds = [];
            foreach ($data['competitors'] as $c) {
                $row = $this->encode($c) + ['updated_at' => $now];
                $key = ['platform' => $c['platform'], 'handle' => $c['handle']];
                $exists = DB::table('fi_competitors')->where($key)->exists();
                if (! $exists) {
                    $row['created_at'] = $now;
                }
                DB::table('fi_competitors')->updateOrInsert($key, $row);
                $compIds[$c['platform'].':'.$c['handle']] = DB::table('fi_competitors')->where($key)->value('id');
            }
            $counts['fi_competitors'] = count($compIds);

            // posts
            $postIds = [];
            foreach ($data['posts'] as $p) {
                $ck = $p['competitor_platform'].':'.$p['competitor_handle'];
                unset($p['competitor_platform'], $p['competitor_handle']);
                $p['competitor_id'] = $compIds[$ck] ?? throw new RuntimeException("Unknown competitor {$ck}");
                $row = $this->encode($p) + ['updated_at' => $now];
                $key = ['platform' => $p['platform'], 'external_id' => $p['external_id']];
                if (! DB::table('fi_competitor_posts')->where($key)->exists()) {
                    $row['created_at'] = $now;
                }
                DB::table('fi_competitor_posts')->updateOrInsert($key, $row);
                $postIds[$p['platform'].':'.$p['external_id']] = DB::table('fi_competitor_posts')->where($key)->value('id');
            }
            $counts['fi_competitor_posts'] = count($postIds);

            // formats
            foreach ($data['formats'] as $f) {
                $row = $this->encode($f) + ['updated_at' => $now];
                if (! DB::table('fi_competitor_formats')->where('code', $f['code'])->exists()) {
                    $row['created_at'] = $now;
                }
                DB::table('fi_competitor_formats')->updateOrInsert(['code' => $f['code']], $row);
            }
            $counts['fi_competitor_formats'] = count($data['formats']);

            // product map — replace rows for the posts we seed; refresh our_* live (read-only)
            DB::table('fi_competitor_product_map')->whereIn('competitor_post_id', array_values($postIds))->delete();
            $live = $this->liveProducts(array_filter(array_column($data['product_map'], 'product_id')));
            foreach ($data['product_map'] as $m) {
                $pk = $m['post_platform'].':'.$m['post_external_id'];
                $ck = $m['competitor_platform'].':'.$m['competitor_handle'];
                unset($m['post_platform'], $m['post_external_id'], $m['competitor_platform'], $m['competitor_handle']);
                $m['competitor_post_id'] = $postIds[$pk] ?? throw new RuntimeException("Unknown post {$pk}");
                $m['competitor_id'] = $compIds[$ck] ?? throw new RuntimeException("Unknown competitor {$ck}");
                if (! empty($m['product_id']) && isset($live[$m['product_id']])) {
                    $lp = $live[$m['product_id']];
                    $m['our_name'] = $lp['name'];
                    $m['our_sku'] = $lp['sku'];
                    $m['our_enabled'] = $lp['enabled'];
                    $m['our_stock_qty'] = $lp['qty'];
                    $m['our_reserved_qty'] = $lp['reserved'];
                    $m['our_price'] = $lp['price'];
                    $m['price_flag'] = $this->priceFlag($m['their_price'] ?? null, $m['their_price_max'] ?? null, $lp['price']);
                }
                unset($m['their_price_max']);
                DB::table('fi_competitor_product_map')->insert($this->encode($m) + ['created_at' => $now, 'updated_at' => $now]);
            }
            $counts['fi_competitor_product_map'] = count($data['product_map']);

            // hashtags
            foreach ($data['hashtags'] as $h) {
                $row = $this->encode($h) + ['updated_at' => $now];
                if (! DB::table('fi_hashtag_library')->where('tag', $h['tag'])->exists()) {
                    $row['created_at'] = $now;
                }
                DB::table('fi_hashtag_library')->updateOrInsert(['tag' => $h['tag']], $row);
            }
            $counts['fi_hashtag_library'] = count($data['hashtags']);

            // staging attributes (proposals only)
            foreach (array_chunk($data['staging_attributes'], 500) as $chunk) {
                DB::table('fi_product_staging_attributes')->upsert(
                    array_map(fn ($r) => $this->encode($r) + ['status' => $r['status'] ?? 'proposed', 'created_at' => $now, 'updated_at' => $now], $chunk),
                    ['product_id', 'attribute', 'source'],
                    ['value', 'confidence', 'evidence', 'internal_only', 'updated_at']
                );
            }
            $counts['fi_product_staging_attributes'] = count($data['staging_attributes']);

            // format fit
            foreach (array_chunk($data['format_fit'], 500) as $chunk) {
                DB::table('fi_product_format_fit')->upsert(
                    array_map(fn ($r) => $r + ['created_at' => $now, 'updated_at' => $now], $chunk),
                    ['product_id', 'format_code'],
                    ['score', 'reason', 'updated_at']
                );
            }
            $counts['fi_product_format_fit'] = count($data['format_fit']);

            // summary
            foreach ($data['summary_rows'] as $s) {
                $row = $this->encode($s) + ['updated_at' => $now];
                $key = ['section' => $s['section'], 'item_key' => $s['item_key']];
                if (! DB::table('fi_competitor_summary')->where($key)->exists()) {
                    $row['created_at'] = $now;
                }
                DB::table('fi_competitor_summary')->updateOrInsert($key, $row);
            }
            $counts['fi_competitor_summary'] = count($data['summary_rows']);
        });

        foreach ($counts as $t => $n) {
            echo "{$t}: {$n}\n";
        }
    }

    /** Read-only lookup of our price / enabled / stock for mapped products. */
    private function liveProducts(array $ids): array
    {
        if (! $ids) {
            return [];
        }
        $flat = DB::table('product_flat')->where('channel', 'default')->where('locale', 'en')
            ->whereIn('product_id', $ids)->get(['product_id', 'sku', 'name', 'status', 'price', 'special_price'])->keyBy('product_id');
        $qty = DB::table('product_inventories')->whereIn('product_id', $ids)
            ->groupBy('product_id')->selectRaw('product_id, SUM(qty) q')->pluck('q', 'product_id');
        $res = DB::table('product_ordered_inventories')->whereIn('product_id', $ids)
            ->groupBy('product_id')->selectRaw('product_id, SUM(qty) q')->pluck('q', 'product_id');

        $out = [];
        foreach ($flat as $pid => $p) {
            $special = (float) ($p->special_price ?? 0);
            $out[$pid] = [
                'name' => $p->name,
                'sku' => $p->sku,
                'enabled' => (bool) $p->status,
                'qty' => (int) ($qty[$pid] ?? 0),
                'reserved' => (int) ($res[$pid] ?? 0),
                'price' => $special > 0 ? $special : (float) $p->price,
            ];
        }

        return $out;
    }

    private function priceFlag($theirMin, $theirMax, $ours): string
    {
        if ($theirMin === null || $ours === null) {
            return 'unknown';
        }
        $theirMax ??= $theirMin;
        if ($theirMax < $ours) {
            return 'lower';   // their price is lower than ours
        }
        if ($theirMin > $ours) {
            return 'higher';  // their price is higher than ours
        }

        return 'same_range';
    }

    private function encode(array $row): array
    {
        foreach (self::JSON_FIELDS as $f) {
            if (array_key_exists($f, $row) && ! is_null($row[$f]) && ! is_string($row[$f])) {
                $row[$f] = json_encode($row[$f], JSON_UNESCAPED_UNICODE);
            }
        }

        return $row;
    }
}
