<?php

namespace Fujiba\AutoPoster\Console\Commands\Intel;

use Fujiba\AutoPoster\Services\Intel\CatalogIssueScanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Scan the catalog (product_flat, one channel, every locale) for code/name
 * inconsistencies and upsert PROPOSALS into fi_catalog_issues.
 * Read-only on Bagisto tables — no live fixes, ever.
 */
class CatalogIssuesCommand extends Command
{
    protected $signature = 'fujiba:intel:catalog-issues
        {--tenant=1bd1181f-fcd5-4238-a12d-a207d6ea4006}
        {--channel=default}
        {--examples=10 : example rows to print}
        {--dry-run : scan and print, write nothing}';

    protected $description = 'Find SKU/name/colour/locale/duplicate/url_key inconsistencies → fi_catalog_issues (proposals only)';

    /**
     * Human-reviewed notes (photos + descriptions inspected 2026-10-11, WS-J).
     * Keyed "issue_type:product_id"; used only while the scanner still detects
     * the issue. Colour words come from the product photos — owner confirms.
     */
    public const REVIEWED = [
        'sku_name_mismatch:5305' => [
            'proposed_fix' => "SKU is right (FK-22D): name[en] and name[my] 'FK-21D' → 'FK-22D'; url_key[en] and url_key[my] 'FK-21Dのコピー' → 'fk-22d' (same as ja). Leave SKU, ja name and ja url_key as they are.",
            'evidence'     => 'KNOWN ISSUE (seeded). Photo of #5305 is the FK-22 small-flower diagonal-band print in a 4th colourway (lilac/purple) — the same design as FK-22A/B/C (#5308 coral, #5307 sage, #5306 red) and NOT the FK-21 design (#5301 FK-21A, #5304 FK-21D: larger flowers/grasses); the barcode image uploaded with the photo is named FK-22D.png; ja name and ja url_key already say FK-22D; FK-22C/B/A url_keys chain from "fk-22d"; FK-21D is product #5304, so en/my "FK-21D" + "FK-21Dのコピー" are leftovers from copying #5304.',
            'confidence'   => 0.95,
            'severity'     => 'high',
            'source'       => 'ws_j_known_seed',
        ],
        'name_locale_mismatch:5305' => [
            'proposed_fix' => "Same root cause as sku_name_mismatch #5305: set name[en] and name[my] to 'FK-22D' (and url_key[en,my] to 'fk-22d').",
            'evidence'     => 'Copy leftover from #5304 (FK-21D); photo, barcode file name, SKU and ja name all say FK-22D.',
            'confidence'   => 0.95,
            'severity'     => 'high',
            'source'       => 'ws_j_known_seed',
        ],
        'colour_letter_mismatch:2020' => [
            'proposed_fix' => "change 'Color: A (Black)' → 'Color: C (Beige)'",
            'evidence'     => 'KNOWN ISSUE (seeded). Photos FV-83-C.jpg/.jpeg show a beige/cream ground with large peonies and red hexagons; siblings are FV-83-A black (#2106), FV-83-B red (#2031), FV-83-D blue (#2023), so "A (Black)" is FV-83-A\'s line copied. Owner may prefer "Cream".',
            'confidence'   => 0.85,
            'source'       => 'ws_j_known_seed',
        ],
        'colour_letter_mismatch:1977' => [
            'proposed_fix' => "change 'Color: A' → 'Color: D' (photo: multicolour seigaiha — blue/purple/cream/gold; owner to name the colour)",
            'evidence'     => 'photo FW-89-D.jpg reviewed',
            'confidence'   => 0.7,
        ],
        'colour_letter_mismatch:2002' => [
            'proposed_fix' => "change 'Color: A (Orange)' → 'Color: B (Black + Pink)' (from the photo; owner to confirm)",
            'evidence'     => 'photo FX-81-B.jpg: pink/white blossoms on a black ground with pale diagonal bands; nothing orange visible',
            'confidence'   => 0.6,
        ],
        'colour_letter_mismatch:2010' => [
            'proposed_fix' => "change 'Color: D (Red)' → 'Color: B (Purple + Teal)' (from the photo; owner to confirm — FV-44-B and FV-44-D colour lines look swapped/garbled)",
            'evidence'     => 'photo FV-44-B.jpg: lilac/purple hexagons, teal waves, red lattice; sibling FV-44-D (#2241) is the pink/red one',
            'confidence'   => 0.5,
        ],
        'colour_letter_mismatch:2037' => [
            'proposed_fix' => "change 'Color: A (Black)' → 'Color: C (Purple)'",
            'evidence'     => 'photo FV-48-C.jpg: purple wave bands with gold on a dark ground',
            'confidence'   => 0.75,
        ],
        'colour_letter_mismatch:2096' => [
            'proposed_fix' => "change 'Color: A (Black)' → 'Color: B (Light blue)'",
            'evidence'     => 'photo FW-105-B.jpg: pink sakura on a light-blue ground',
            'confidence'   => 0.8,
        ],
        'colour_letter_mismatch:2201' => [
            'proposed_fix' => "change 'Color: B (Blue)' → 'Color: G (Blue)'",
            'evidence'     => 'photo: navy/blue ground with teal-and-gold flowers — the colour word fits, only the letter is wrong',
            'confidence'   => 0.8,
        ],
        'colour_letter_mismatch:2241' => [
            'proposed_fix' => "change 'Color: C (Green)' → 'Color: D (Pink + Red)' (from the photo; owner to confirm)",
            'evidence'     => 'photo: pink hexagons, red lattice, white blossoms; nothing green dominant',
            'confidence'   => 0.6,
        ],
        'colour_letter_mismatch:2419' => [
            'proposed_fix' => "change 'Color: A (Black)' → 'Color: C (Dark green)'",
            'evidence'     => 'photo: white plum blossoms and gold balls on a dark-green ground',
            'confidence'   => 0.8,
        ],
    ];

    public const KNOWN = ['sku_name_mismatch:default:5305', 'colour_letter_mismatch:default:2020'];

    public function handle(): int
    {
        if (function_exists('tenancy') && ! tenancy()->initialized) {
            tenancy()->initialize($this->option('tenant'));
        }
        $channel = (string) $this->option('channel');
        $defaultLocale = DB::table('channels as c')->join('locales as l', 'l.id', '=', 'c.default_locale_id')
            ->where('c.code', $channel)->value('l.code') ?? 'ja';

        $products = [];
        foreach (DB::table('product_flat')->where('channel', $channel)->orderBy('product_id')->orderBy('locale')
            ->get(['product_id', 'sku', 'locale', 'name', 'url_key', 'description', 'status']) as $r) {
            $p = &$products[(int) $r->product_id];
            $p['sku'] ??= $r->sku;
            if (! isset($p['description']) || $r->locale === $defaultLocale) {
                $p['description'] = $r->description;
                $p['status'] = (int) $r->status;
            }
            $p['locales'][$r->locale] = ['name' => $r->name, 'url_key' => $r->url_key];
            unset($p);
        }
        $ids = array_keys($products);
        $stock = DB::table('product_inventories')->whereIn('product_id', $ids)->groupBy('product_id')->selectRaw('product_id, SUM(qty) q')->pluck('q', 'product_id');
        $res = DB::table('product_ordered_inventories')->whereIn('product_id', $ids)->groupBy('product_id')->selectRaw('product_id, SUM(qty) q')->pluck('q', 'product_id');
        foreach ($products as $pid => &$p) {
            $p['available_qty'] = (int) ($stock[$pid] ?? 0) - (int) ($res[$pid] ?? 0);
        }
        unset($p);

        $out = (new CatalogIssueScanner($channel, $defaultLocale, self::REVIEWED))->scan($products);
        $issues = $out['issues'];

        $this->info("channel {$channel} (default locale {$defaultLocale}) — ".json_encode($out['counts']));
        $byType = collect($issues)->groupBy('issue_type');
        $this->table(['issue_type', 'rows', 'products', 'high', 'medium', 'low'], collect(CatalogIssueScanner::TYPES)->map(fn ($t) => [
            $t, count($byType[$t] ?? []), collect($byType[$t] ?? [])->pluck('product_id')->unique()->count(),
            collect($byType[$t] ?? [])->where('severity', 'high')->count(),
            collect($byType[$t] ?? [])->where('severity', 'medium')->count(),
            collect($byType[$t] ?? [])->where('severity', 'low')->count(),
        ])->all());

        $fps = array_column($issues, 'fingerprint');
        foreach (self::KNOWN as $k) {
            $this->line(in_array($k, $fps, true) ? "  known issue detected: {$k}" : "<comment>  known issue NOT detected (data changed?): {$k}</comment>");
        }

        $rank = ['high' => 0, 'medium' => 1, 'low' => 2];
        $examples = collect($issues)->sortBy(fn ($i) => [$rank[$i['severity']] ?? 3, $i['issue_type'], $i['product_id']])->take((int) $this->option('examples'));
        foreach ($examples as $i) {
            $this->line(sprintf('  [%s] %s #%d %s %s | now: %s | fix: %s', $i['severity'], $i['issue_type'], $i['product_id'],
                $i['name'], $i['locale'] ? "({$i['locale']})" : '', mb_strimwidth($i['current_value'], 0, 90, '…'), mb_strimwidth($i['proposed_fix'], 0, 140, '…')));
        }

        if ($this->option('dry-run')) {
            $this->warn('dry-run: nothing written');

            return self::SUCCESS;
        }

        $now = now();
        $ins = $upd = 0;
        DB::transaction(function () use ($issues, $now, &$ins, &$upd) {
            foreach ($issues as $i) {
                $data = $i + ['last_seen_at' => $now, 'updated_at' => $now, 'internal_only' => 1];
                $existing = DB::table('fi_catalog_issues')->where('fingerprint', $i['fingerprint'])->first(['id']);
                if ($existing) {
                    DB::table('fi_catalog_issues')->where('id', $existing->id)->update($data);   // status/review fields untouched
                    $upd++;
                } else {
                    DB::table('fi_catalog_issues')->insert($data + ['status' => 'proposed', 'created_at' => $now]);
                    $ins++;
                }
            }
        });
        $this->info("fi_catalog_issues: {$ins} inserted, {$upd} updated, total ".DB::table('fi_catalog_issues')->count());

        return self::SUCCESS;
    }
}
