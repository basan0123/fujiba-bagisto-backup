<?php

namespace Fujiba\SupplierFinder\Services;

use Fujiba\SupplierFinder\Models\CrawledProduct;
use Fujiba\SupplierFinder\Models\FabricMatch;
use Fujiba\SupplierFinder\Models\SourceFabric;

/**
 * Scores every live crawled product against our reference set.
 *  EXACT  : same decoded design key (e.g. naka:1750-67), or near-identical
 *           print (pHash distance ≤ exact_phash_distance).
 *  SIMILAR: weighted image (palette + texture + pHash) / text (style + motif
 *           words) / material+width score ≥ similar_min_score.
 * Without a learned embedding (CLIP — pending owner approval) SIMILAR is a
 * "same look & feel" signal, not semantic pattern understanding.
 */
class Matcher
{
    public function __construct(private ReferenceCatalog $refs, private array $config) {}

    public function run(): array
    {
        $refs = $this->refs->load();
        $byKey = collect($refs)->filter(fn ($r) => self::designKey($r->design_key))->groupBy(fn ($r) => self::designKey($r->design_key));
        $crawled = CrawledProduct::with('fabric')->whereNull('gone_at')->get();

        // Teach our designs their motifs: an EXACT code match's shop title
        // ("1750【#67 鞠と牡丹】") names the motifs our alias names lack.
        foreach ($crawled as $p) {
            foreach ($byKey[self::designKey($p->fabric?->base_code) ?? '∅'] ?? [] as $r) {
                $r->style = array_values(array_unique([...$r->style, ...$this->words($p->title)]));
            }
        }
        $cfg = $this->config['match'];
        $stats = ['exact' => 0, 'similar' => 0, 'none' => 0, 'refs' => count($refs), 'refs_with_image' => collect($refs)->whereNotNull('features')->count()];

        FabricMatch::query()->delete();

        foreach ($crawled as $p) {
            $scored = [];
            $exactRefs = $byKey[self::designKey($p->fabric?->base_code) ?? '∅'] ?? collect();

            foreach ($refs as $r) {
                $s = $this->score($p, $r);
                if ($exactRefs->contains('product_id', $r->product_id)) {
                    $s['code'] = 1.0;
                    $s['type'] = 'exact';
                    $s['total'] = max(0.9, $s['total']);
                } elseif ($s['phash_d'] !== null && $s['phash_d'] <= $cfg['exact_phash_distance']) {
                    $s['type'] = 'exact';
                    $s['total'] = max(0.85, $s['total']);
                } else {
                    $s['type'] = $s['total'] >= $cfg['similar_min_score'] ? 'similar' : null;
                }
                if ($s['type']) {
                    $scored[] = [$r, $s];
                }
            }

            usort($scored, fn ($a, $b) => [$b[1]['type'] === 'exact', $b[1]['total']] <=> [$a[1]['type'] === 'exact', $a[1]['total']]);
            foreach (array_slice($scored, 0, $cfg['keep_per_product']) as [$r, $s]) {
                FabricMatch::create([
                    'crawled_product_id' => $p->id, 'product_id' => $r->product_id, 'match_type' => $s['type'],
                    'score' => round($s['total'], 3), 'code_score' => $s['code'], 'image_score' => round($s['image'], 3),
                    'color_score' => round($s['color'], 3), 'text_score' => round($s['text'], 3), 'material_score' => round($s['material'], 3),
                    'details' => ['phash_d' => $s['phash_d'], 'words' => $s['words'], 'ref_code' => $r->code, 'ref_name' => $r->name],
                ]);
            }
            $stats[$scored ? $scored[0][1]['type'] : 'none']++;
        }

        $this->rollUpFabrics();

        return $stats;
    }

    private function score(CrawledProduct $p, object $r): array
    {
        $fa = $p->features;
        $fb = $r->features;
        $phashD = ($p->phash && ($fb['phash'] ?? null)) ? ImageFeatures::hamming($p->phash, $fb['phash']) : null;

        $color = ($fa && $fb) ? ImageFeatures::paletteSimilarity($fa['palette'] ?? [], $fb['palette'] ?? []) : 0.0;
        $texture = ($fa && $fb) ? max(0, 1 - (abs($fa['edge'] - $fb['edge']) * 6 + abs($fa['sat'] - $fb['sat']) * 1.5 + abs($fa['gold'] - $fb['gold']) * 4)) : 0.0;
        $phashSim = $phashD === null ? 0.0 : max(0.0, (32 - $phashD) / 32);
        $image = 0.5 * $color + 0.35 * $texture + 0.15 * $phashSim;

        // text: crawled title must look Japanese-style; motif/style words shared with our design add more
        $words = $this->words($p->title.' '.$p->series);
        $shared = array_intersect($words, $r->style);
        $text = ($words ? 0.6 : 0.0) + min(0.4, 0.2 * count($shared));

        $material = 0.0;
        if ($p->material && $r->material) {
            $cotton = ['細布', 'シーチング', 'ブロード', 'スケアー', 'オックス', 'ツイル'];
            $material = $p->material === $r->material ? 1.0 : (in_array($p->material, $cotton) && in_array($r->material, $cotton) ? 0.6 : 0.2);
        } elseif ($p->material !== 'ポリエステル') {
            $material = 0.4;   // unknown → neutral
        }
        if ($p->width_cm && ($p->width_cm < 88 || $p->width_cm > 150)) {
            $material *= 0.5;
        }

        return [
            'total' => 0.55 * $image + 0.25 * $text + 0.20 * $material,
            'image' => $image, 'color' => $color, 'text' => $text, 'material' => $material, 'code' => 0.0,
            'phash_d' => $phashD, 'words' => $words,
        ];
    }

    private function words(string $text): array
    {
        return array_values(array_filter([...$this->config['motif_words'], ...$this->config['style_words']], fn ($w) => mb_strpos($text, $w) !== false));
    }

    /** naka:2500-147N ≡ naka:2500-147 — shops drop Naka's chirimen "N" and say 綿ちりめん instead. */
    private static function designKey(?string $key): ?string
    {
        // item: = shop item with no code at all — never a design identity
        return $key && ! str_starts_with($key, 'item:') ? preg_replace('/(\d)N$/', '$1', $key) : null;
    }

    private function rollUpFabrics(): void
    {
        SourceFabric::query()->update(['match_type' => 'none', 'match_score' => null, 'matched_product_id' => null]);
        $best = FabricMatch::query()->join('sf_crawled_products as c', 'c.id', '=', 'sf_matches.crawled_product_id')
            ->whereNotNull('c.source_fabric_id')
            ->orderByRaw("sf_matches.match_type = 'exact' desc, sf_matches.score desc")
            ->get(['c.source_fabric_id', 'sf_matches.match_type', 'sf_matches.score', 'sf_matches.product_id'])
            ->unique('source_fabric_id');
        foreach ($best as $b) {
            SourceFabric::whereKey($b->source_fabric_id)->update(['match_type' => $b->match_type, 'match_score' => $b->score, 'matched_product_id' => $b->product_id]);
        }
    }
}
