<?php

namespace Tests\Unit\Fujiba;

use App\Services\Fujiba\ProductNameParser;
use PHPUnit\Framework\TestCase;

class ProductNameParserTest extends TestCase
{
    private ProductNameParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new ProductNameParser();
    }

    /** @dataProvider tierStyleColorProvider */
    public function test_tier_style_color_shape(string $raw, string $tier, string $style, string $color, string $sep): void
    {
        $r = $this->parser->parse($raw);
        $this->assertSame(ProductNameParser::SHAPE_TIER_STYLE_COLOR, $r['source_shape'], "raw={$raw}");
        $this->assertSame($tier, $r['tier']);
        $this->assertSame($style, $r['style']);
        $this->assertSame($color, $r['color_letter']);
        $this->assertSame($sep, $r['separator']);
        $this->assertSame($raw, $r['raw']);
        $this->assertSame(ProductNameParser::VERSION, $r['parser_version']);
        $this->assertArrayHasKey('parsed_at', $r);
    }

    public static function tierStyleColorProvider(): array
    {
        return [
            ['2500_155N_H', '2500', '155N', 'H', '_'],
            ['1750_70_C',   '1750', '70',   'C', '_'],
            ['2800_5_E',    '2800', '5',    'E', '_'],
            ['1750_80_H',   '1750', '80',   'H', '_'],
            ['1100_2_E',    '1100', '2',    'E', '_'],
            ['2700_11_C',   '2700', '11',   'C', '_'],
            ['1100_5_A',    '1100', '5',    'A', '_'],
            ['2000_81_A',   '2000', '81',   'A', '_'],
            ['1600_76_A',   '1600', '76',   'A', '_'],
            ['1600_106_A',  '1600', '106',  'A', '_'],
            ['2000_53_B',   '2000', '53',   'B', '_'],
            ['2000_73_C',   '2000', '73',   'C', '_'],
            ['1750_66_B',   '1750', '66',   'B', '_'],
            ['2200_84_B',   '2200', '84',   'B', '_'],
            ['1600_95_C',   '1600', '95',   'C', '_'],
            ['1750_88_B',   '1750', '88',   'B', '_'],
            ['1750_86_A',   '1750', '86',   'A', '_'],
            ['1750_48_A',   '1750', '48',   'A', '_'],
            ['1100_6_A',    '1100', '6',    'A', '_'],
            ['1750_32_C',   '1750', '32',   'C', '_'],
            ['1750_88_A',   '1750', '88',   'A', '_'],
            ['1600_97_D',   '1600', '97',   'D', '_'],
            ['2200_81_C',   '2200', '81',   'C', '_'],
            ['1600_97_B',   '1600', '97',   'B', '_'],
            ['1750_74_D',   '1750', '74',   'D', '_'],
            ['2800_5-D',    '2800', '5',    'D', '-'],
            ['1200_4-B',    '1200', '4',    'B', '-'],
            ['1200_4_E',    '1200', '4',    'E', '_'],
            ['1200_3_A',    '1200', '3',    'A', '_'],
            ['1200_3_B',    '1200', '3',    'B', '_'],
            ['1400_1_A',    '1400', '1',    'A', '_'],
            ['1400_1_C',    '1400', '1',    'C', '_'],
            ['1400_3_A',    '1400', '3',    'A', '_'],
            ['1400_3_B',    '1400', '3',    'B', '_'],
            ['1500_2_A',    '1500', '2',    'A', '_'],
            ['1500_2_C',    '1500', '2',    'C', '_'],
            ['1500_1_B',    '1500', '1',    'B', '_'],
            ['1500_3_B',    '1500', '3',    'B', '_'],
            ['2200_81_A',   '2200', '81',   'A', '_'],
            ['2600_2_B',    '2600', '2',    'B', '_'],
            ['2900_2_A',    '2900', '2',    'A', '_'],
            ['2900_2_B',    '2900', '2',    'B', '_'],
            ['2900_2_C',    '2900', '2',    'C', '_'],
            ['leading whitespace' => ' 1750_44_D', '1750', '44', 'D', '_'],
            ['leading whitespace 2' => ' 1100_1_B', '1100', '1', 'B', '_'],
            ['leading whitespace 3' => ' 1200_4_C', '1200', '4', 'C', '_'],
            ['leading whitespace 4' => ' 2900_1_C', '2900', '1', 'C', '_'],
            ['mixed separators' => '2000_102-B', '2000', '102', 'B', 'mixed'],
        ];
    }

    /** @dataProvider apSeriesProvider */
    public function test_ap_series_shape(string $raw, string $apSeries, string $sub, string $color, string $sep): void
    {
        $r = $this->parser->parse($raw);
        $this->assertSame(ProductNameParser::SHAPE_AP_SERIES, $r['source_shape'], "raw={$raw}");
        $this->assertSame('AP', $r['prefix']);
        $this->assertSame($apSeries, $r['ap_series']);
        $this->assertSame($sub, $r['sub']);
        $this->assertSame($color, $r['color_letter']);
        $this->assertSame($sep, $r['separator']);
    }

    public static function apSeriesProvider(): array
    {
        return [
            ['AP42901-2C',   '42901',  '2', 'C', '-'],
            ['AP52902H_2A',  '52902H', '2', 'A', '_'],
            ['AP52902H_2D',  '52902H', '2', 'D', '_'],
            ['AP45103_1A',   '45103',  '1', 'A', '_'],
            ['AP42901-3A',   '42901',  '3', 'A', '-'],
            ['AP42901-2D',   '42901',  '2', 'D', '-'],
        ];
    }

    public function test_b_prefixed_shape(): void
    {
        $r = $this->parser->parse('B_1000_31_A');
        $this->assertSame(ProductNameParser::SHAPE_B_PREFIXED, $r['source_shape']);
        $this->assertSame('B_', $r['prefix']);
        $this->assertSame('1000', $r['tier']);
        $this->assertSame('31', $r['style']);
        $this->assertSame('A', $r['color_letter']);
        $this->assertSame('_', $r['separator']);
    }

    public function test_fb_series_shape(): void
    {
        $r = $this->parser->parse('Fb_42110_3E');
        $this->assertSame(ProductNameParser::SHAPE_FB_SERIES, $r['source_shape']);
        $this->assertSame('Fb_', $r['prefix']);
        $this->assertSame('42110', $r['ap_series']);
        $this->assertSame('3', $r['sub']);
        $this->assertSame('E', $r['color_letter']);
        $this->assertSame('_', $r['separator']);
    }

    /** @dataProvider hr3530hProvider */
    public function test_hr3530h_shape(string $raw, string $sub, string $color): void
    {
        $r = $this->parser->parse($raw);
        $this->assertSame(ProductNameParser::SHAPE_HR3530H, $r['source_shape'], "raw={$raw}");
        $this->assertSame('HR3530H', $r['prefix']);
        $this->assertSame('3530H', $r['hr_series']);
        $this->assertSame($sub, $r['sub']);
        $this->assertSame($color, $r['color_letter']);
        $this->assertSame('-', $r['separator']);
    }

    public static function hr3530hProvider(): array
    {
        return [
            ['HR3530H -11D', '11', 'D'],
            ['HR3530H-11F',  '11', 'F'],
            ['HR3530H-11C',  '11', 'C'],
            ['HR3530H-12C',  '12', 'C'],
            ['HR3530H-12D',  '12', 'D'],
            ['HR3530H-12E',  '12', 'E'],
            ['HR3530H-11A',  '11', 'A'],
            ['HR3530H-11B',  '11', 'B'],
        ];
    }

    /** @dataProvider unparseableProvider */
    public function test_unparseable(?string $raw, string $reason): void
    {
        $r = $this->parser->parse($raw);
        $this->assertSame(ProductNameParser::SHAPE_UNPARSEABLE, $r['source_shape']);
        $this->assertSame($reason, $r['reason']);
        $this->assertArrayNotHasKey('color_letter', $r);
    }

    public static function unparseableProvider(): array
    {
        return [
            'truncated_no_color' => ['1600-91',     'no_pattern_matched'],
            'lowercase_color'    => ['1750_70_c',   'no_pattern_matched'],
            'two_letter_color'   => ['1750_70_AB',  'no_pattern_matched'],
            'no_separator'       => ['175070C',     'no_pattern_matched'],
            'wrong_ap_sub'       => ['AP42901-22C', 'no_pattern_matched'],
            'null'               => [null,          'empty'],
            'empty_string'       => ['',            'empty'],
            'whitespace_only'    => ['   ',         'empty'],
        ];
    }

    public function test_output_shape_is_stable(): void
    {
        $r = $this->parser->parse('1750_70_C', '2026-05-04T00:00:00Z');
        $this->assertSame([
            'raw'            => '1750_70_C',
            'parsed_at'      => '2026-05-04T00:00:00Z',
            'parser_version' => 1,
            'source_shape'   => 'tier_style_color',
            'tier'           => '1750',
            'style'          => '70',
            'color_letter'   => 'C',
            'separator'      => '_',
        ], $r);
    }
}
