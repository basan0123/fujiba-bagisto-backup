<?php

namespace Fujiba\SupplierFinder\Adapters;

use Fujiba\SupplierFinder\Models\SourceShop;

/** やまよ — Color Me Shop (EUC-JP). Detail pages embed `var Colorme = {...}` with variants + stock (units of 10cm). */
class YamayoAdapter extends ShopAdapter
{
    public function crawl(SourceShop $shop): \Generator
    {
        foreach ($shop->start_urls ?? [] as $start) {
            $seen = [];
            for ($page = 1; $page <= $this->config['http']['max_pages']; $page++) {
                $html = $this->utf8($this->http->get($start.'&page='.$page));
                preg_match_all('/href="\?pid=(\d+)"/', $html, $m);
                $new = array_diff(array_unique($m[1]), $seen);
                if (! $new) {
                    break;
                }
                foreach ($new as $pid) {
                    $seen[] = $pid;
                    yield $this->detail($pid);
                }
            }
        }
    }

    private function detail(string $pid): array
    {
        $url = "https://yamayo-nippori.com/?pid={$pid}";
        $html = $this->utf8($this->http->get($url));
        $data = preg_match('/var Colorme = (\{.*?\});\s*$/m', $html, $m) ? json_decode($m[1], true) : null;
        $p = $data['product'] ?? [];
        $title = self::norm($p['name'] ?? (preg_match('/<title>([^<]+?) - /u', $html, $t) ? $t[1] : $pid));
        $text = self::norm(strip_tags($html));

        $colorways = [];
        foreach ($p['variants'] ?? [] as $v) {
            $colorways[] = ['code' => $v['model_number'] ?: null, 'label' => self::norm($v['title']), 'stock' => $v['stock_num']];
        }

        $unit = preg_match('/x\s*(\d+)\s*cm/u', $text, $u) ? $u[1].'cm' : '10cm';
        if ($unit === '100cm') {
            $unit = '1m';
        }

        return [
            'external_id' => $pid,
            'url'         => $url,
            'title'       => $title,
            'codes'       => self::codes($title),
            'series'      => self::series($title),
            'maker'       => preg_match('/(?:メーカー|ブランド)\s*[:：]\s*([^\s、。]{2,30})/u', $text, $mk) ? $mk[1] : null,
            'material'    => $this->material($title) ?? $this->material($this->desc($html)) ?? (str_contains($text, '綿') ? '綿' : null),
            'width_cm'    => self::width($title) ?? self::width($text),
            'colorways'   => $colorways,
            'price'       => $p['sales_price_including_tax'] ?? null,
            'price_unit'  => $unit,
            'min_order'   => '1 = '.$unit,
            'stock'       => $p['stock_num'] ?? null,
            'image_urls'  => preg_match('/og:image" content="([^"]+)"/', $html, $i) ? [$i[1]] : [],
            'raw'         => $html,
            'raw_ext'     => 'html',
        ];
    }

    private function desc(string $html): string
    {
        return preg_match('/<div[^>]*product_explain[^>]*>(.*?)<\/div>/su', $html, $m) ? strip_tags($m[1]) : '';
    }

    private function utf8(string $body): string
    {
        return mb_check_encoding($body, 'UTF-8') ? $body : mb_convert_encoding($body, 'UTF-8', 'EUC-JP');
    }
}
