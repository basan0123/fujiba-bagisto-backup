<?php

namespace Fujiba\AutoPoster\Services\Intel;

use Illuminate\Support\Facades\DB;

/**
 * Evidence research for one product SKU.
 *
 * Two evidence kinds, both traced to a fetched, cached source:
 *  1. Supplier (Naka Trading) — via the supplier code in `original_product_name`
 *     and the crawl cache `supplier_image_sync_sources` built by SupplierImageSync.
 *     These rows contain the SECRET supplier code: topic=supplier_internal and
 *     internal_only=true. They must never reach captions or public pages.
 *  2. Reference pages (Wikipedia JA/EN) for terms that actually appear in the
 *     supplier text (match_type=exact) or only in our own catalogue text
 *     (match_type=generic — e.g. the fabric, not the specific design).
 *
 * Rules: a fact is only written with a verbatim excerpt from the cached page;
 * nothing is inferred or translated into "meaning". No excerpt → no fact.
 * Cosmo codes (config intel.research.cosmo_codes) are never treated as Naka.
 *
 * Writes only fi_research_sources and fi_facts (fields prefixed "research.").
 */
class ProductResearchService
{
    public const MAX_EXCERPT = 200;

    /** term (as it appears in text) => [wiki lang, article title, kind] */
    public const TERMS = [
        // motifs — only counted when present in SUPPLIER text
        '麻の葉' => ['ja', '麻の葉', 'motif'], '青海波' => ['ja', '青海波', 'motif'],
        '市松' => ['ja', '市松模様', 'motif'], '矢絣' => ['ja', '矢絣', 'motif'],
        '七宝' => ['ja', '七宝文', 'motif'], '亀甲' => ['ja', '亀甲文', 'motif'],
        '鱗' => ['ja', '鱗文', 'motif'], '唐草' => ['ja', '唐草文様', 'motif'],
        '紗綾形' => ['ja', '紗綾形', 'motif'], '雪輪' => ['ja', '雪輪', 'motif'],
        '桜' => ['ja', 'サクラ', 'motif'], '椿' => ['ja', 'ツバキ', 'motif'],
        '牡丹' => ['ja', 'ボタン_(植物)', 'motif'], '菊' => ['ja', 'キク', 'motif'],
        '金魚' => ['ja', 'キンギョ', 'motif'], '鶴' => ['ja', 'ツル', 'motif'],
        // fabric / category — generic context
        '和柄' => ['ja', '和柄', 'generic'], 'ちりめん' => ['ja', '縮緬', 'generic'],
        '縮緬' => ['ja', '縮緬', 'generic'], 'chirimen' => ['ja', '縮緬', 'generic'],
        '綿' => ['ja', '木綿', 'generic'], 'cotton' => ['en', 'Cotton', 'generic'],
        'Traditional Japanese pattern' => ['ja', '和柄', 'generic'],
    ];

    public function __construct(private PoliteFetcher $fetcher, private array $cfg = []) {}

    public static function make(): self
    {
        $cfg = config('fujiba_intel.research', []);
        $fetcher = new PoliteFetcher(
            $cfg['cache_dir'] ?? storage_path('app/intel/research-cache'),
            $cfg['allowed_hosts'] ?? ['naka-trading.jp', 'ja.wikipedia.org', 'en.wikipedia.org'],
            (float) ($cfg['min_interval_seconds'] ?? 5),
        );

        return new self($fetcher, $cfg);
    }

    /** @return array{sku:string,product_id:?int,supplier:string,facts:int,match:string,notes:array} */
    public function research(string $sku, bool $dryRun = false): array
    {
        $r = ['sku' => $sku, 'product_id' => null, 'supplier' => 'none', 'facts' => 0, 'match' => 'none', 'notes' => []];

        $p = DB::table('product_flat')->where('sku', $sku)->where('locale', 'en')->orderBy('id')->first();
        if (! $p) {
            $r['notes'][] = 'sku not found';

            return $r;
        }
        $r['product_id'] = (int) $p->product_id;
        $attr = DB::table('attributes')->where('code', 'original_product_name')->value('id');
        $opn = (string) DB::table('product_attribute_values')->where('product_id', $p->product_id)
            ->where('attribute_id', $attr)->value('text_value');

        $facts = [];
        $supplierText = '';

        // ---- 1. supplier evidence -----------------------------------------
        $code = strtoupper(str_replace('_', '-', trim($opn)));
        $isCosmo = $code !== '' && collect($this->cfg['cosmo_codes'] ?? ['AP42901', 'AP45103', 'AP52902H'])
            ->contains(fn ($c) => str_starts_with($code, $c));

        if ($isCosmo) {
            $r['supplier'] = 'cosmo (no catalogue mapping)';
            $r['notes'][] = 'Cosmo code — not Naka; no Cosmo source configured';
        } elseif ($code !== '' && $this->tableExists('supplier_image_sync_sources')) {
            $row = DB::table('supplier_image_sync_sources')->where('naka_code', $code)->first();
            $supplierMatch = 'exact';
            if (! $row) {
                $base = preg_replace('/-[A-Z]{1,2}$/', '', $code);
                $row = DB::table('supplier_image_sync_sources')->where('base_code', $base)->first();
                $supplierMatch = 'similar';
            }

            if ($row) {
                $res = $this->fetcher->get(rtrim($this->cfg['naka_base'] ?? 'https://naka-trading.jp', '/')
                    .'/wp-json/wp/v2/item/'.(int) $row->item_id);
                $srcId = $this->storeSource($res, $r['product_id'], 'supplier_internal', $dryRun, internal: true);
                $json = $res['body'] ? json_decode($res['body'], true) : null;

                if ($json) {
                    $title = trim(html_entity_decode(strip_tags($json['title']['rendered'] ?? ''), ENT_QUOTES));
                    $content = $this->clean($json['content']['rendered'] ?? '');
                    $supplierText = $title.' '.$content;
                    $r['supplier'] = 'naka';

                    if ($title !== '') {
                        $facts[] = ['research.supplier.design', 'Supplier catalogue lists this design', $title,
                            $supplierMatch, 0.95, $srcId, 'fact', true];
                    }
                    // colour codes live only in the item HTML; SupplierImageSync's crawl
                    // cache recorded them from that page, so cite the cache row.
                    if ($supplierMatch === 'exact' && $row->color_label) {
                        $facts[] = ['research.supplier.colour', 'Supplier crawl cache lists this colour variant',
                            (string) $row->color_label, 'exact', 0.85, $srcId, 'fact', true];
                    }
                    if ($content !== '') {
                        $facts[] = ['research.supplier.text', 'Supplier item description',
                            mb_substr($content, 0, self::MAX_EXCERPT), $supplierMatch, 0.85, $srcId, 'fact', true];
                    }
                } else {
                    $r['notes'][] = 'supplier page unavailable: '.($res['error'] ?? 'no body');
                    // fall back to the crawl cache row itself (already gathered from the supplier site)
                    $facts[] = ['research.supplier.design', 'Supplier crawl cache lists this design',
                        (string) $row->title, $supplierMatch, 0.7, $srcId, 'fact', true];
                }
            } else {
                $r['notes'][] = 'supplier code not in Naka crawl cache';
            }
        } else {
            $r['notes'][] = 'no supplier code';
        }

        // ---- 2. reference pages for terms ----------------------------------
        $ownText = $p->name.' '.strip_tags((string) $p->description);
        $seen = [];
        foreach (self::TERMS as $term => [$lang, $title, $kind]) {
            $inSupplier = $supplierText !== '' && mb_stripos($supplierText, $term) !== false;
            $inOwn = mb_stripos($ownText, $term) !== false;
            if ($kind === 'motif' && ! $inSupplier) {
                continue;      // a motif is only claimed when the supplier names it
            }
            if (! $inSupplier && ! $inOwn) {
                continue;
            }
            if (isset($seen["$lang:$title"])) {
                continue;
            }
            $seen["$lang:$title"] = true;

            $url = "https://{$lang}.wikipedia.org/wiki/".rawurlencode($title);
            $res = $this->fetcher->get($url);
            $srcId = $this->storeSource($res, $r['product_id'], 'cultural_reference', $dryRun, pattern: $term);
            $excerpt = $res['body'] ? $this->wikiLead($res['body']) : '';
            if ($excerpt === '') {
                $r['notes'][] = "no excerpt for {$lang}:{$title} (".($res['error'] ?? 'empty').')';

                continue;
            }
            $match = ($kind === 'motif' && $inSupplier) ? 'exact' : 'generic';
            $facts[] = ["research.reference.{$kind}", "Reference text for “{$term}” ({$lang}.wikipedia: {$title})",
                $excerpt, $match, $match === 'exact' ? 0.75 : 0.4, $srcId, 'web_research', false];
        }

        // ---- write ----------------------------------------------------------
        if (! $dryRun) {
            DB::table('fi_facts')->where('product_id', $r['product_id'])->where('field', 'like', 'research.%')->delete();
            foreach ($facts as [$field, $claim, $excerpt, $match, $conf, $srcId, $type, $internal]) {
                DB::table('fi_facts')->insert([
                    'product_id' => $r['product_id'], 'field' => $field,
                    'value' => json_encode(['claim' => $claim, 'evidence_excerpt' => $excerpt,
                        'match_type' => $match, 'internal_only' => $internal], JSON_UNESCAPED_UNICODE),
                    'source_type' => $type, 'confidence' => $conf, 'source_ref_id' => $srcId,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        $r['facts'] = count($facts);
        $matches = array_column($facts, 3);
        $r['match'] = in_array('exact', $matches, true) ? 'exact'
            : (in_array('similar', $matches, true) ? 'similar' : ($matches ? 'generic' : 'none'));
        $r['fact_rows'] = $facts;

        return $r;
    }

    private function storeSource(array $res, int $productId, string $topic, bool $dryRun, bool $internal = false, ?string $pattern = null): ?int
    {
        if ($dryRun) {
            return null;
        }
        $summary = json_encode([
            'http_status' => $res['status'], 'robots_ok' => $res['robots_ok'], 'cache_path' => $res['cache_path'],
            'fetched_at' => $res['fetched_at'], 'from_cache' => $res['from_cache'], 'error' => $res['error'],
            'internal_only' => $internal,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $existing = DB::table('fi_research_sources')->where('product_id', $productId)
            ->where('topic', $topic)->where('url', $res['url'])->value('id');
        $row = [
            'url' => $res['url'], 'publisher' => parse_url($res['url'], PHP_URL_HOST),
            'retrieved_at' => $res['fetched_at'], 'finding_summary' => $summary, 'topic' => $topic,
            'confidence' => $res['body'] ? 0.9 : 0.0, 'product_id' => $productId, 'pattern' => $pattern,
            'updated_at' => now(),
        ];
        if ($existing) {
            DB::table('fi_research_sources')->where('id', $existing)->update($row);

            return (int) $existing;
        }

        return (int) DB::table('fi_research_sources')->insertGetId($row + ['created_at' => now()]);
    }

    /** First non-empty lead paragraph of a Wikipedia article, verbatim (refs stripped). */
    public function wikiLead(string $html): string
    {
        if (! preg_match_all('#<p\b[^>]*>(.*?)</p>#su', $html, $m)) {
            return '';
        }
        foreach ($m[1] as $para) {
            $t = $this->clean(preg_replace('#<sup\b.*?</sup>#su', '', $para));
            if (mb_strlen($t) >= 20) {
                return mb_substr($t, 0, self::MAX_EXCERPT);
            }
        }

        return '';
    }

    private function clean(string $html): string
    {
        $t = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $t));
    }

    private function tableExists(string $t): bool
    {
        return \Illuminate\Support\Facades\Schema::hasTable($t);
    }
}
