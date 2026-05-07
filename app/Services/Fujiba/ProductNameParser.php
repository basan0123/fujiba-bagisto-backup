<?php

namespace App\Services\Fujiba;

class ProductNameParser
{
    public const VERSION = 1;

    public const SHAPE_TIER_STYLE_COLOR = 'tier_style_color';
    public const SHAPE_B_PREFIXED       = 'b_prefixed';
    public const SHAPE_AP_SERIES        = 'ap_series';
    public const SHAPE_FB_SERIES        = 'fb_series';
    public const SHAPE_HR3530H          = 'hr3530h';
    public const SHAPE_UNPARSEABLE      = 'unparseable';

    public function parse(?string $raw, ?string $nowIso = null): array
    {
        $rawForOutput = (string) ($raw ?? '');
        $nowIso       = $nowIso ?? gmdate('Y-m-d\TH:i:s\Z');

        $base = [
            'raw'            => $rawForOutput,
            'parsed_at'      => $nowIso,
            'parser_version' => self::VERSION,
        ];

        if ($raw === null || trim($raw) === '') {
            return $base + [
                'source_shape' => self::SHAPE_UNPARSEABLE,
                'reason'       => 'empty',
            ];
        }

        $s = trim($raw);

        if (preg_match('/^(?<prefix>B_)(?<tier>\d{3,4})(?<sep1>[_\-])(?<style>\d+N?)(?<sep2>[_\-])(?<color>[A-Z])$/', $s, $m)) {
            return $base + [
                'source_shape' => self::SHAPE_B_PREFIXED,
                'prefix'       => $m['prefix'],
                'tier'         => $m['tier'],
                'style'        => $m['style'],
                'color_letter' => $m['color'],
                'separator'    => $this->collapseSeparators($m['sep1'], $m['sep2']),
            ];
        }

        if (preg_match('/^(?<tier>\d{3,4})(?<sep1>[_\-])(?<style>\d+N?)(?<sep2>[_\-])(?<color>[A-Z])$/', $s, $m)) {
            return $base + [
                'source_shape' => self::SHAPE_TIER_STYLE_COLOR,
                'tier'         => $m['tier'],
                'style'        => $m['style'],
                'color_letter' => $m['color'],
                'separator'    => $this->collapseSeparators($m['sep1'], $m['sep2']),
            ];
        }

        if (preg_match('/^AP(?<ap_series>\d+H?)(?<sep>[_\-])(?<sub>\d)(?<color>[A-Z])$/', $s, $m)) {
            return $base + [
                'source_shape' => self::SHAPE_AP_SERIES,
                'prefix'       => 'AP',
                'ap_series'    => $m['ap_series'],
                'sub'          => $m['sub'],
                'color_letter' => $m['color'],
                'separator'    => $m['sep'],
            ];
        }

        if (preg_match('/^Fb_(?<ap_series>\d+H?)(?<sep>[_\-])(?<sub>\d)(?<color>[A-Z])$/', $s, $m)) {
            return $base + [
                'source_shape' => self::SHAPE_FB_SERIES,
                'prefix'       => 'Fb_',
                'ap_series'    => $m['ap_series'],
                'sub'          => $m['sub'],
                'color_letter' => $m['color'],
                'separator'    => $m['sep'],
            ];
        }

        if (preg_match('/^HR3530H\s*-(?<sub>\d{2})(?<color>[A-Z])$/', $s, $m)) {
            return $base + [
                'source_shape' => self::SHAPE_HR3530H,
                'prefix'       => 'HR3530H',
                'hr_series'    => '3530H',
                'sub'          => $m['sub'],
                'color_letter' => $m['color'],
                'separator'    => '-',
            ];
        }

        return $base + [
            'source_shape' => self::SHAPE_UNPARSEABLE,
            'reason'       => 'no_pattern_matched',
        ];
    }

    private function collapseSeparators(string $a, string $b): string
    {
        return $a === $b ? $a : 'mixed';
    }
}
