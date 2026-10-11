<?php

namespace Fujiba\AutoPoster\Tests\Unit\Intel;

use Fujiba\AutoPoster\Services\Intel\PublicCode;
use PHPUnit\Framework\TestCase;

class PublicCodeTest extends TestCase
{
    public function test_parses_every_public_code_shape_in_the_catalog(): void
    {
        $cases = [
            'FV-83-C'                                        => ['FV83C', 'C', 'FV83'],
            'FK-22D'                                         => ['FK22D', 'D', 'FK22'],
            'FZ-5-E'                                         => ['FZ5E', 'E', 'FZ5'],
            'FU-106-A'                                       => ['FU106A', 'A', 'FU106'],
            'FY-155N-H (ဝေဖာချည်) (chirimen cotton) (ちりめん)' => ['FY155NH', 'H', 'FY155N'],
            'FV-65-H((ピンク+銀))'                           => ['FV65H', 'H', 'FV65'],
            'FK-17C (purple)ခရမ်း🟣'                          => ['FK17C', 'C', 'FK17'],
            'FB-HS-PPL'                                      => ['FBHSPPL', 'PPL', 'FBHS'],
            'fv-80-h-k2467'                                  => ['FV80H', 'H', 'FV80'],
            'fk-22dのコピーのコピー'                          => ['FK22D', 'D', 'FK22'],
            'fy-136n-a-k2606のコピー'                         => ['FY136NA', 'A', 'FY136N'],
        ];
        foreach ($cases as $in => [$code, $colour, $pattern]) {
            $p = PublicCode::parse($in);
            $this->assertNotNull($p, $in);
            $this->assertSame($code, $p['code'], $in);
            $this->assertSame($colour, $p['colour'], $in);
            $this->assertSame($pattern, $p['pattern'], $in);
        }
    }

    public function test_separator_variants_normalise_to_the_same_code(): void
    {
        $this->assertSame(PublicCode::code('FK-11A'), PublicCode::code('fk_11-a'));
        $this->assertNotSame(PublicCode::code('FK-21D'), PublicCode::code('FK-22D'));
    }

    public function test_words_and_supplier_codes_are_not_public_codes(): void
    {
        foreach (['japanese Fan 30001', 'DesiFlower_G', 'WaterFlow_N', 'japanese-fabric-30001', 'Fb_42901-2C',
            'FB_42110_3E', 'Fb-3510H-11B', 'kimono-2467', 'Kamakura Dibutsu (4-5)寸', ''] as $s) {
            $this->assertNull(PublicCode::parse($s), $s);
        }
    }

    public function test_supplier_code_detection(): void
    {
        foreach (['Fb_42901-2C', 'FB_42110_3E', 'Fb-3510H-11B', 'Fb-7078-A', 'Fb_AP62902L-A(3m)ဝမ်းဆက်စာ', 'AP62902L-B',
            'AP42901', '1750-75A', '1950-201-A', 'fb-42901-2c-k2475', 'x 1750-75A y'] as $s) {
            $this->assertTrue(PublicCode::hasSupplierCode($s), $s);
        }
        foreach (['FV-83-C', 'FK-22D', 'FB-HS-PPL', 'FB-MR-A', 'KIMONO-2467', 'kimono-2467', 'fv-80-h-k2467',
            'temporary-sku-5348da', 'japanese Fan 30001', 'checked 2026-10-11', '¥1500-1600'] as $s) {
            $this->assertFalse(PublicCode::hasSupplierCode($s), $s);
        }
    }

    public function test_mask_hides_only_the_supplier_code(): void
    {
        $this->assertSame('[supplier-code]-2C', PublicCode::mask('Fb_42901-2C'));
        $this->assertSame('[supplier-code]-B(3m) ဝမ်းဆက်စာ', PublicCode::mask('Fb_AP62902L-B(3m) ဝမ်းဆက်စာ'));
        $this->assertSame('FV-83-C', PublicCode::mask('FV-83-C'));
        $this->assertStringNotContainsString('1750', PublicCode::mask('code 1750-75A here'));
    }

    public function test_is_public_code_requires_the_whole_string(): void
    {
        $this->assertTrue(PublicCode::isPublicCode('FK-22D'));
        $this->assertTrue(PublicCode::isPublicCode(' FV-83-C '));
        $this->assertFalse(PublicCode::isPublicCode('KIMONO-2593'));
        $this->assertFalse(PublicCode::isPublicCode('FK-22D copy'));
        $this->assertSame('fk-22d', PublicCode::slug('FK-22D'));
    }
}
