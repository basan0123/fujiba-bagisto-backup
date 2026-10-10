<?php

namespace Fujiba\SupplierFinder\Adapters;

use Fujiba\SupplierFinder\Models\SourceShop;

/**
 * YAMATOMI (EC-CUBE). Listing has 品番 / 混率 / 一般価格 (¥/m); detail adds
 * width (112cmx23m), unit choices (反物 / m) and one image per colour (_YM_n).
 * 一般価格 is the public price — business-member prices are behind login and not crawled.
 */
class YamatomiAdapter extends ShopAdapter
{
    public function crawl(SourceShop $shop): \Generator
    {
        foreach ($shop->start_urls ?? [] as $start) {
            $seen = [];
            for ($page = 1; $page <= $this->config['http']['max_pages']; $page++) {
                $html = $this->http->get($start.'&pageno='.$page);
                $items = $this->listing($html);
                $items = array_filter($items, fn ($i) => ! isset($seen[$i['id']]));
                if (! $items) {
                    break;
                }
                foreach ($items as $item) {
                    $seen[$item['id']] = true;
                    yield $this->detail($item);
                }
            }
        }
    }

    private function listing(string $html): array
    {
        $out = [];
        foreach (preg_split('/<div class="product_item/', $html) as $i => $block) {
            if ($i === 0 || ! preg_match('/detail\.php\?product_id=(\d+)/', $block, $id)) {
                continue;
            }
            $out[] = [
                'id'    => $id[1],
                'title' => preg_match('/<h3>\s*<a[^>]*>([^<]+)<\/a>/u', $block, $t) ? self::norm($t[1]) : '',
                'code'  => preg_match('/品番：\s*([^<\s]+)/u', $block, $c) ? self::norm($c[1]) : null,
                'mix'   => preg_match('/混率：\s*([^<]+)/u', $block, $x) ? self::norm($x[1]) : null,
                'price' => preg_match('/([\d,]+)円\/m/u', $block, $p) ? (int) str_replace(',', '', $p[1]) : null,
            ];
        }

        return $out;
    }

    private function detail(array $item): array
    {
        $url = "https://yamatomi.biz/products/detail.php?product_id={$item['id']}";
        $html = $this->http->get($url);
        $text = self::norm(strip_tags($html));
        $main = preg_match('/<img src="(\/upload\/products_image\/[^"]+)"[^>]*class="picture"/u', $html, $m) ? $m[1] : null;

        $colorways = [];
        $images = $main ? [self::abs($url, $main)] : [];
        if ($main && preg_match_all('#<img src="('.preg_quote(preg_replace('/\.\w+$/', '', $main), '#').'_YM_(\d+)\.\w+)"#', $html, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $c) {
                $colorways[] = ['code' => $c[2], 'label' => '#'.$c[2], 'image' => self::abs($url, $c[1])];
                $images[] = self::abs($url, $c[1]);
            }
        }
        $tanM = preg_match('/\d{2,3}\s*cm\s*x\s*(\d+)\s*m/u', $text, $t) ? (int) $t[1] : null;
        $min = preg_match('#(1m\s*〜[^/]{0,40})#u', $text, $mo) ? trim($mo[1]) : '1m';

        return [
            'external_id' => $item['id'],
            'url'         => $url,
            'title'       => $item['title'],
            'codes'       => array_values(array_filter([$item['code'] ? strtoupper($item['code']) : null, ...self::codes($item['title'])])),
            'series'      => self::series($item['title']),
            // 混率 wins: 二越ちりめん is polyester even though the title says ちりめん
            'material'    => str_contains((string) $item['mix'], 'ポリエステル') ? 'ポリエステル' : $this->material($item['title']),
            'width_cm'    => self::width($text),
            'colorways'   => $colorways,
            'price'       => $item['price'],
            'price_unit'  => '1m',
            'tan_m'       => $tanM,
            'min_order'   => $min,
            'stock'       => null,
            'image_urls'  => array_values(array_unique($images)),
            'raw'         => $html,
            'raw_ext'     => 'html',
        ];
    }
}
