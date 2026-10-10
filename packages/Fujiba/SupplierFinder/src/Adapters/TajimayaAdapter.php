<?php

namespace Fujiba\SupplierFinder\Adapters;

use Fujiba\SupplierFinder\Models\SourceShop;

/**
 * 布の但馬屋 (futureshop). Category listing → /view/item/{id}; the detail
 * "アイテム説明" table has 品番 / 価格 / 素材 / 色 / メーカー. Older items only have
 * 独自商品コード and no メーカー row, so maker stays null and the code rules decide.
 */
class TajimayaAdapter extends ShopAdapter
{
    public function crawl(SourceShop $shop): \Generator
    {
        foreach ($shop->start_urls ?? [] as $start) {
            $seen = [];
            for ($page = 1; $page <= $this->config['http']['max_pages']; $page++) {
                $html = $this->http->get($start.(str_contains($start, '?') ? '&' : '?').'page='.$page);
                preg_match_all('#/view/item/(\d+)#', $html, $m);
                $new = array_diff(array_unique($m[1]), $seen);
                if (! $new) {
                    break;
                }
                foreach ($new as $id) {
                    $seen[] = $id;
                    yield $this->detail($id);
                }
            }
        }
    }

    private function detail(string $id): array
    {
        $url = "https://www.tajimaya.net/view/item/{$id}";
        $html = $this->http->get($url);
        $text = self::norm(preg_replace('#<(script|style)\b.*?</\1>#su', ' ', $html));
        $field = fn (string $k, string $next) => preg_match('/'.$k.'\s+(.*?)\s+'.$next.'/u', $text, $m) ? trim($m[1]) : null;

        $title = preg_match('/<title>\s*([^|<]+)/u', $html, $t) ? self::norm($t[1]) : $id;
        $code = $field('品番', '価格') ?? (preg_match('/独自商品コード\s*:\s*(\S+)/u', $text, $c) ? $c[1] : null);
        $maker = $field('メーカー', '再入荷');

        $colorways = [];
        if (preg_match('/【色】(.*?)商品がカートに/u', $text, $cm)
            && preg_match_all('/([A-Z])\s*([^\s¥￥]{1,10})\s*[¥￥]\s*([\d,]+)\s*[(（]税込[)）]\s*(売り切れ|在庫あり(?:\s*\/あと(\d+)個)?)/u', $cm[1], $vv, PREG_SET_ORDER)) {
            foreach ($vv as $v) {
                $colorways[] = ['code' => $v[1], 'label' => $v[1].' '.$v[2], 'stock' => $v[4] === '売り切れ' ? 0 : (isset($v[5]) && $v[5] !== '' ? (int) $v[5] : null)];
            }
        }

        // main photo = {id}_{hash}.jpg, colour shots = {id}_{n}_{hash}.jpg
        preg_match_all('#https://[^"\'\s]+/itemimages/'.$id.'_[^"\'\s]+?\.(?:jpe?g|png)#i', $html, $im);
        $images = array_values(array_unique($im[0]));
        usort($images, fn ($a, $b) => (int) preg_match('#_\d+_[^/]+$#', $a) <=> (int) preg_match('#_\d+_[^/]+$#', $b) ?: strnatcmp($a, $b));

        return [
            'external_id' => $id,
            'url'         => $url,
            'title'       => $title,
            'codes'       => array_values(array_unique(array_filter([$code ? strtoupper($code) : null, ...self::codes($title)]))),
            'series'      => self::series($title),
            'maker'       => $maker !== '' ? $maker : null,
            'material'    => $this->material(($field('織物・組織名', '素材') ?? '').' '.$title.' '.$text),
            'width_cm'    => self::width($text),
            'colorways'   => $colorways,
            'price'       => preg_match('/[¥￥]\s*([\d,]+)\s*[(（]税込[)）]/u', $text, $p) ? (int) str_replace(',', '', $p[1]) : null,
            'price_unit'  => '10cm',
            'min_order'   => '10cm',
            'stock'       => $colorways ? array_sum(array_map(fn ($c) => (int) $c['stock'], $colorways)) : null,
            'image_urls'  => array_slice($images, 0, 8),
            // makeshop/rakuten image CDNs answer 403 to robots.txt → PoliteHttp treats that as disallow; keep the URLs only.
            'no_image_download' => true,
            'raw'         => $html,
            'raw_ext'     => 'html',
        ];
    }
}
