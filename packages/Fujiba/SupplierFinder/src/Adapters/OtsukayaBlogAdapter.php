<?php

namespace Fujiba\SupplierFinder\Adapters;

use Fujiba\SupplierFinder\Models\SourceShop;

/**
 * 大塚屋 — the shop itself (otsukaya.co.jp/store) sits behind an AWS WAF bot
 * challenge, which we do not get around. Their public blog otsukaya.net
 * (WordPress) introduces the fabrics, so each post = one item: title,
 * featured image, and the maker when the post names one (コットンこばやし…).
 */
class OtsukayaBlogAdapter extends ShopAdapter
{
    private const MAKERS = ['コットンこばやし' => 'コットンこばやし (小林繊維)', '小林繊維' => 'コットンこばやし (小林繊維)', 'コスモテキスタイル' => 'コスモテキスタイル',
        'ナカトレーディング' => 'ナカトレーディング', 'KOKKA' => 'KOKKA', 'コッカ' => 'KOKKA', '双日' => '双日ファッション', '北高' => '北高', 'YUWA' => 'YUWA', '有輪' => 'YUWA', 'ルシアン' => 'ルシアン'];

    public function crawl(SourceShop $shop): \Generator
    {
        foreach ($shop->start_urls ?? [] as $start) {
            $seen = [];
            for ($page = 1; $page <= $this->config['http']['max_pages']; $page++) {
                try {
                    $html = $this->http->get($page === 1 ? $start : rtrim($start, '/')."/page/{$page}");
                } catch (\RuntimeException $e) {
                    if ($page > 1 && str_contains($e->getMessage(), 'HTTP 404')) {
                        break;   // past the last listing page
                    }
                    throw $e;
                }
                preg_match_all('#https://otsukaya\.net/archives/(\d+)"#', $html, $m);
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
        $url = "https://otsukaya.net/archives/{$id}";
        $html = $this->http->get($url);
        $body = preg_match('#<article\b.*?</article>#su', $html, $a) ? $a[0] : $html;
        $text = self::norm(preg_replace('#<(script|style)\b.*?</\1>#su', ' ', $body));
        $title = preg_match('/og:title" content="([^"]+)"/u', $html, $t) ? self::norm($t[1]) : $id;

        $maker = null;
        foreach (self::MAKERS as $needle => $name) {
            if (str_contains($title.' '.$text, $needle)) {
                $maker = $name;
                break;
            }
        }

        $images = preg_match('/og:image" content="([^"]+)"/', $html, $og) ? [$og[1]] : [];
        preg_match_all('#https://otsukaya\.net/wp-content/uploads/[^"\'\s]+?\.(?:jpe?g|png)#i', $body, $im);
        $images = array_values(array_unique(array_merge($images, preg_grep('/-\d+x\d+\./', $im[0], PREG_GREP_INVERT))));
        preg_match_all('#https://otsukaya\.co\.jp/store/products/detail/\d+#', $body, $links);

        return [
            'external_id' => $id,
            'url'         => $url,
            'title'       => mb_substr($title, 0, 200),
            'codes'       => self::codes($title.' '.$text),
            'series'      => self::series($title),
            'maker'       => $maker,
            'material'    => $this->material($title.' '.$text),
            'width_cm'    => self::width($text),
            'colorways'   => array_map(fn ($l) => ['code' => null, 'label' => $l, 'stock' => null], array_values(array_unique($links[0]))),
            'price'       => null,
            'price_unit'  => null,
            'stock'       => null,
            'image_urls'  => array_slice($images, 0, 8),
            'raw'         => $html,
            'raw_ext'     => 'html',
        ];
    }
}
