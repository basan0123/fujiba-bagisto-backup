<?php

namespace Fujiba\AutoPoster\Tests\Unit\Intel;

use Fujiba\AutoPoster\Services\Intel\CatalogIssueScanner;
use Fujiba\AutoPoster\Services\Intel\RestockCandidateService;
use PHPUnit\Framework\TestCase;

class CatalogIssueScannerTest extends TestCase
{
    private function p(string $sku, array $names, array $urls = [], int $status = 1, ?string $desc = null, int $avail = 1): array
    {
        $loc = [];
        foreach ($names as $l => $n) {
            $loc[$l] = ['name' => $n, 'url_key' => $urls[$l] ?? strtolower($n)];
        }

        return ['sku' => $sku, 'status' => $status, 'description' => $desc, 'available_qty' => $avail, 'locales' => $loc];
    }

    private function scan(array $products, array $reviewed = []): array
    {
        return (new CatalogIssueScanner('default', 'ja', $reviewed))->scan($products);
    }

    private function ofType(array $issues, string $type): array
    {
        return array_values(array_filter($issues, fn ($i) => $i['issue_type'] === $type));
    }

    public function test_fk22d_copy_leftover_is_sku_name_and_locale_mismatch(): void
    {
        $out = $this->scan([
            5304 => $this->p('FK-21D', ['en' => 'FK-21D', 'ja' => 'FK-21D', 'my' => 'FK-21D']),
            5305 => $this->p('FK-22D', ['en' => 'FK-21D', 'ja' => 'FK-22D', 'my' => 'FK-21D'],
                ['en' => 'FK-21Dのコピー', 'ja' => 'fk-22d', 'my' => 'FK-21Dのコピー']),
        ]);
        $sku = $this->ofType($out['issues'], 'sku_name_mismatch');
        $this->assertCount(1, $sku);
        $this->assertSame(5305, $sku[0]['product_id']);
        $this->assertSame('en,my', $sku[0]['locale']);
        $this->assertStringContainsString("'FK-21D' → 'FK-22D'", $sku[0]['proposed_fix']);
        $this->assertSame('high', $sku[0]['severity']);
        $this->assertSame(0.8, $sku[0]['confidence']);

        $loc = $this->ofType($out['issues'], 'name_locale_mismatch');
        $this->assertCount(1, $loc);
        $this->assertSame('en,my', $loc[0]['locale']);
        // url_key FK-21Dのコピー matches the (wrong) en name → not a url_key issue; ja name decides duplicates
        $this->assertCount(0, $this->ofType($out['issues'], 'url_key_name_mismatch'));
        $this->assertCount(0, $this->ofType($out['issues'], 'duplicate_name'));
        $this->assertSame(1, $out['counts']['url_key_copy_suffix']);
    }

    public function test_colour_letter_mismatch_with_sibling_copy_evidence(): void
    {
        $out = $this->scan([
            2106 => $this->p('KIMONO-2680', ['en' => 'FV-83-A', 'ja' => 'FV-83-A'], [], 0, '<p>Product code: FV-83-A<br>Color: A (Black)<br>x</p>'),
            2020 => $this->p('KIMONO-2593', ['en' => 'FV-83-C', 'ja' => 'FV-83-C'], [], 1, '<p>Product code: FV-83-C<br>Color: A (Black)<br>x</p>'),
            2031 => $this->p('KIMONO-2604', ['en' => 'FV-83-B', 'ja' => 'FV-83-B'], [], 1, '<p>Color: B (Red)</p>'),
            1903 => $this->p('KIMONO-1', ['ja' => 'FW-1-C'], [], 1, '<p>Color: C</p>'),
        ]);
        $c = $this->ofType($out['issues'], 'colour_letter_mismatch');
        $this->assertCount(1, $c);
        $this->assertSame(2020, $c[0]['product_id']);
        $this->assertSame('Color: A (Black)', $c[0]['current_value']);
        $this->assertStringContainsString("Color: C (", $c[0]['proposed_fix']);
        $this->assertStringContainsString('#2106', $c[0]['evidence']);
        $this->assertStringContainsString("'Product code: FV-83-C' agrees", $c[0]['evidence']);
        $this->assertNull($c[0]['locale']);
        $this->assertSame('medium', $c[0]['severity']);
    }

    public function test_reviewed_note_overrides_fix_and_prefixes_evidence(): void
    {
        $out = $this->scan([
            2020 => $this->p('KIMONO-2593', ['ja' => 'FV-83-C'], [], 1, '<p>Color: A (Black)</p>'),
        ], ['colour_letter_mismatch:2020' => ['proposed_fix' => "→ 'Color: C (Beige)'", 'evidence' => 'photo', 'confidence' => 0.85, 'source' => 'ws_j_known_seed']]);
        $c = $this->ofType($out['issues'], 'colour_letter_mismatch')[0];
        $this->assertSame("→ 'Color: C (Beige)'", $c['proposed_fix']);
        $this->assertSame(0.85, $c['confidence']);
        $this->assertSame('ws_j_known_seed', $c['source']);
        $this->assertStringStartsWith('photo || scan: ', $c['evidence']);
    }

    public function test_duplicates_severity_and_length_variants(): void
    {
        $out = $this->scan([
            1 => $this->p('K-1', ['ja' => 'FV-61-C'], [], 0),
            2 => $this->p('K-2', ['ja' => 'FV-61-C'], [], 1),
            3 => $this->p('K-3', ['ja' => 'FV-61-A'], [], 1),
            4 => $this->p('K-4', ['ja' => 'FR-4-E'], [], 1),
            5 => $this->p('K-5', ['ja' => 'FR-4-E(3m)ဝမ်းဆက်စာ'], [], 1),
        ]);
        $d = $this->ofType($out['issues'], 'duplicate_name');
        $this->assertCount(4, $d);
        $byPid = array_column($d, null, 'product_id');
        $this->assertSame('low', $byPid[1]['severity']);                    // only one of the two enabled
        $this->assertStringContainsString('FV-61-D', $byPid[1]['proposed_fix']);   // next free letter after C
        $this->assertSame('high', $byPid[4]['severity']);                   // both enabled
        $this->assertStringContainsString('length variants', $byPid[4]['proposed_fix']);
    }

    public function test_url_key_mismatch_and_supplier_names_skipped(): void
    {
        $out = $this->scan([
            5290 => $this->p('FK-11B', ['en' => 'FK-11B', 'ja' => 'FK-11B'], ['en' => 'FK-11Aのコピー', 'ja' => 'fk-11b'], 0),
            1902 => $this->p('KIMONO-2475', ['en' => 'Fb_42901-2C', 'ja' => 'Fb_42901-2C'], ['en' => 'fb-42901-2c-k2475', 'ja' => 'fb-42901-2c-k2475']),
            1894 => $this->p('KIMONO-2467', ['en' => 'FV-80-H', 'ja' => 'FV-80-H', 'my' => 'FV-80-H'], ['en' => 'fv-80-h-k2467', 'ja' => 'fv-80-h-k2467', 'my' => 'kimono-2467']),
        ]);
        $u = $this->ofType($out['issues'], 'url_key_name_mismatch');
        $this->assertCount(1, $u);
        $this->assertSame(5290, $u[0]['product_id']);
        $this->assertSame('en', $u[0]['locale']);
        $this->assertStringContainsString("url_key[en] → 'fk-11b'", $u[0]['proposed_fix']);

        // an upper-case / non-slug default-locale url_key is never copied — a clean slug is proposed
        $out2 = $this->scan([
            5292 => $this->p('FK-11D', ['en' => 'FK-11D', 'ja' => 'FK-11D'], ['en' => 'FK-11Aのコピー', 'ja' => 'FK-11D']),
        ]);
        $this->assertStringContainsString("url_key[en] → 'fk-11d'", $this->ofType($out2['issues'], 'url_key_name_mismatch')[0]['proposed_fix']);
        $this->assertSame('low', $u[0]['severity']);
        $this->assertSame(1, $out['counts']['supplier_code_in_name_skipped']);
        $this->assertSame(1, $out['counts']['supplier_code_in_url_key']);
        foreach ($out['issues'] as $i) {
            $this->assertNotSame(1902, $i['product_id']);
        }
    }

    public function test_restock_evidence_score_formula_and_sort(): void
    {
        $this->assertSame(3.25, RestockCandidateService::evidenceScore('exact', 1, 1, false));        // 3×1 + 0.25
        $this->assertSame(3.5, RestockCandidateService::evidenceScore('probable', 2, 2, false));      // 2×1.5 + 0.5
        $this->assertSame(3.75, RestockCandidateService::evidenceScore('probable', 2, 1, true));      // 2×1.5 + 0.25 + 0.5
        $this->assertSame(1.25, RestockCandidateService::evidenceScore('possible', 1, 1, false));
        $this->assertSame(0.0, RestockCandidateService::evidenceScore('none', 1, 0, false));

        $sorted = RestockCandidateService::sort([
            ['product_id' => 3, 'evidence_score' => 2.5, 'sold_qty_12m' => 1],
            ['product_id' => 1, 'evidence_score' => 2.5, 'sold_qty_12m' => 9],
            ['product_id' => 2, 'evidence_score' => 4.0, 'sold_qty_12m' => 0],
        ]);
        $this->assertSame([2, 1, 3], array_column($sorted, 'product_id'));
        $this->assertSame([1, 2, 3], array_column($sorted, 'rank'));
    }
}
