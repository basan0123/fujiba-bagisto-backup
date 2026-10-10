<?php

namespace Fujiba\SupplierFinder\Adapters;

use Fujiba\SupplierFinder\Models\SourceShop;

/**
 * 日暮里トマト (Shopify). Uses the public /collections/{handle}/products.json
 * feed (one request per 250 products). Variant SKUs carry the codes (F1-YK-01…).
 * Start URLs are collection URLs; prices are per 10cm unless the title says otherwise.
 */
class TomatoAdapter extends ShopAdapter
{
    public function crawl(SourceShop $shop): \Generator
    {
        $starts = $shop->start_urls ?: $this->discover($shop);

        foreach ($starts as $start) {
            $base = rtrim(preg_replace('/\?.*$/', '', $start), '/');
            for ($page = 1; $page <= $this->config['http']['max_pages']; $page++) {
                $body = $this->http->get("{$base}/products.json?limit=250&page={$page}");
                $products = json_decode($body, true)['products'] ?? [];
                if (! $products) {
                    break;
                }
                foreach ($products as $p) {
                    yield $this->map($shop, $p);
                }
            }
        }
    }

    /** No start URL configured → pick the 和調/和柄 collections from /collections.json. */
    private function discover(SourceShop $shop): array
    {
        $root = rtrim($shop->base_url, '/');
        $cols = json_decode($this->http->get("{$root}/collections.json?limit=250"), true)['collections'] ?? [];

        return collect($cols)->filter(fn ($c) => preg_match('/和調|和柄/u', $c['title']))
            ->map(fn ($c) => "{$root}/collections/{$c['handle']}")->values()->all();
    }

    private function map(SourceShop $shop, array $p): array
    {
        $title = self::norm($p['title']);
        $desc = self::norm(strip_tags($p['body_html'] ?? ''));
        $skus = array_filter(array_column($p['variants'] ?? [], 'sku'));
        $colorways = [];
        foreach ($p['variants'] ?? [] as $v) {
            $colorways[] = ['code' => $v['sku'] ?: null, 'label' => self::norm($v['title']), 'stock' => ($v['available'] ?? true) ? null : 0];
        }
        $unit = preg_match('/(\d+)\s*cm\s*単位|(\d+)cm\s*あたり/u', $desc, $u) ? (($u[1] ?: $u[2]).'cm') : '10cm';

        return [
            'external_id' => (string) $p['id'],
            'url'         => rtrim($shop->base_url, '/').'/products/'.$p['handle'],
            'title'       => $title,
            'codes'       => array_values(array_unique([...array_map('strtoupper', $skus), ...self::codes($title)])),
            'series'      => self::series($title.' '.$desc),
            'material'    => $this->material($title) ?? $this->material($desc),
            'width_cm'    => self::width($title.' '.$desc),
            'colorways'   => $colorways,
            'price'       => isset($p['variants'][0]['price']) ? (int) round((float) $p['variants'][0]['price']) : null,
            'price_unit'  => $unit,
            'min_order'   => preg_match('/(\d+)\s*(?:点|個)?\s*以上|最低[^。]{0,20}/u', $desc, $mo) ? $mo[0] : null,
            'stock'       => collect($p['variants'] ?? [])->contains('available', true) ? null : 0,
            'image_urls'  => array_slice(array_column($p['images'] ?? [], 'src'), 0, 6),
            'raw'         => json_encode($p, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'raw_ext'     => 'json',
        ];
    }
}
