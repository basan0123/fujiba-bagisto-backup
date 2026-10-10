<?php

namespace Fujiba\SupplierFinder\Adapters;

use Fujiba\SupplierFinder\Models\SourceShop;
use Fujiba\SupplierFinder\Services\PoliteHttp;

/**
 * One class per shop. crawl() yields product arrays:
 *   external_id, url, title, codes[], series?, material?, width_cm?,
 *   colorways[{code,label,stock?,image?}], price?, price_unit (10cm|50cm|1m|tan),
 *   tan_m?, min_order?, stock?, image_urls[], raw (body to snapshot), raw_ext
 * Adding a shop = new subclass + set sf_source_shops.adapter to its basename.
 */
abstract class ShopAdapter
{
    public function __construct(protected PoliteHttp $http, protected array $config) {}

    abstract public function crawl(SourceShop $shop): \Generator;

    protected static function norm(string $s): string
    {
        // full-width ASCII/digits → half-width, collapse spaces
        return trim(preg_replace('/\s+/u', ' ', mb_convert_kana(html_entity_decode(strip_tags(str_ireplace('<br>', ' ', $s)), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'as')));
    }

    protected function material(string $text): ?string
    {
        foreach ($this->config['materials'] as $canon => $needles) {
            foreach ($needles as $n) {
                if (mb_stripos($text, $n) !== false) {
                    return $canon;
                }
            }
        }

        return null;
    }

    protected static function width(string $text): ?int
    {
        return preg_match('/(?:巾|幅|width)\s*[:：]?\s*(?:約)?\s*(\d{2,3})\s*cm/iu', $text, $m)
            || preg_match('/(\d{2,3})\s*cm\s*[x×]\s*\d+\s*m/iu', $text, $m)
            ? (int) $m[1] : null;
    }

    /** Generic product-code finder for titles/descriptions; adapters add shop-specific ones. */
    public static function codes(string $text): array
    {
        $text = self::norm($text);
        $out = [];
        // Naka-style "1750【#67" / "1750 #67"
        if (preg_match_all('/(?<![\dA-Za-z-])(\d{4})\s*【?\s*#\s*(\d{1,3}N?)/u', $text, $m, PREG_SET_ORDER)) {
            foreach ($m as $x) {
                $out[] = "{$x[1]}-{$x[2]}";
            }
        }
        if (preg_match_all('/(?<![A-Za-z0-9])((?:F1-)?(?:YK|COCO|KTS|AP|HR|SZ|AS)-?\d{2,6}(?:-\d{1,3})?[A-Z]?)(?![A-Za-z0-9])/i', $text, $m)) {
            array_push($out, ...$m[1]);
        }
        // bare 5–6 digit maker numbers right before 【 (e.g. スケアプリント850585【#1)
        if (preg_match_all('/(?<![\dA-Za-z])(\d{5,6})\s*(?=【)(?:【\s*#\s*(\d{1,3}))?/u', $text, $m, PREG_SET_ORDER)) {
            foreach ($m as $x) {
                $out[] = isset($x[2]) && $x[2] !== '' ? "{$x[1]}-{$x[2]}" : $x[1];
            }
        }
        if (preg_match_all('/(?<![\d-])(\d{4}-\d{1,4})(?![\d])/', $text, $m)) {
            array_push($out, ...$m[1]);
        }

        return array_values(array_unique(array_map('strtoupper', $out)));
    }

    protected static function series(string $title): ?string
    {
        foreach (['京彩華', '和彩', '金雅乃詩', 'にゃんこだ和', 'モダンだ和', '四季彩爛漫', '夢色百景', '開花', 'サザンクロス'] as $s) {
            if (str_contains($title, $s)) {
                return $s;
            }
        }

        return preg_match('/[『「]([^』」]{2,20})[』」]/u', $title, $m) ? $m[1] : null;
    }

    protected static function abs(string $base, string $href): string
    {
        if (str_starts_with($href, '//')) {
            return 'https:'.$href;
        }
        if (preg_match('#^https?://#', $href)) {
            return $href;
        }
        $p = parse_url($base);

        return $p['scheme'].'://'.$p['host'].(str_starts_with($href, '/') ? '' : '/').$href;
    }
}
