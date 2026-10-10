<?php

namespace Fujiba\SupplierFinder\Services;

use Fujiba\SupplierFinder\Models\CrawledProduct;
use Fujiba\SupplierFinder\Models\CrawlRun;
use Fujiba\SupplierFinder\Models\SourceShop;
use Fujiba\SupplierFinder\Support\ShopBlocked;
use Fujiba\SupplierFinder\Support\TenantContext;

class CrawlService
{
    public function __construct(private PoliteHttp $http, private array $config) {}

    public function crawl(SourceShop $shop, ?\Closure $progress = null, ?int $limit = null): CrawlRun
    {
        $run = CrawlRun::create(['shop_id' => $shop->id]);
        $date = now()->format('Ymd');
        $snapDir = TenantContext::dir("raw/{$shop->code}/{$date}");
        $seen = [];
        $diff = ['new' => [], 'price' => [], 'out_of_stock' => [], 'back_in_stock' => [], 'gone' => []];
        $counts = ['items' => 0, 'images' => 0, 'image_errors' => 0];
        $status = 'ok';
        $complete = false;

        try {
            if (! $shop->adapter || ! class_exists($cls = 'Fujiba\\SupplierFinder\\Adapters\\'.$shop->adapter)) {
                throw new \RuntimeException("No adapter for shop {$shop->code}");
            }
            $adapter = new $cls($this->http, $this->config);

            foreach ($adapter->crawl($shop) as $item) {
                $seen[] = $item['external_id'];
                $this->store($shop, $item, $snapDir, $diff, $counts);
                $counts['items']++;
                $progress && $progress($counts['items'], $item['title']);
                if ($limit && $counts['items'] >= $limit) {
                    break;
                }
            }
            $complete = ! $limit;
        } catch (ShopBlocked $e) {
            $status = $e->reason;
            $run->error = $e->getMessage();
        } catch (\Throwable $e) {
            $status = 'error';
            $run->error = $e->getMessage();
            report($e);
        }

        // Only a full, successful crawl may mark items as gone.
        if ($complete && $status === 'ok') {
            $gone = $shop->products()->whereNull('gone_at')->whereNotIn('external_id', $seen)->get();
            foreach ($gone as $g) {
                $g->update(['gone_at' => now()]);
                $diff['gone'][] = $g->id;
            }
        }

        $shop->update(['last_crawled_at' => now(), 'last_status' => $status, 'last_error' => $run->error]);
        $run->fill([
            'status' => $status === 'ok' ? 'done' : $status, 'requests' => $this->http->requestCount(),
            'counts' => $counts, 'diff' => $diff, 'finished_at' => now(),
        ])->save();

        return $run;
    }

    private function store(SourceShop $shop, array $item, string $snapDir, array &$diff, array &$counts): void
    {
        $raw = $item['raw'] ?? '';
        unset($item['raw']);
        $ext = $item['raw_ext'] ?? 'html';
        $file = $snapDir.'/'.preg_replace('/\W/', '_', $item['external_id']);
        file_put_contents("{$file}.{$ext}", $raw);
        file_put_contents("{$file}.json", json_encode($item + ['crawled_at' => now()->toIso8601String()], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $perM = PriceNormalizer::perMetre($item['price'] ?? null, $item['price_unit'] ?? null, $item['tan_m'] ?? null);
        $attrs = [
            'url' => $item['url'], 'title' => mb_substr($item['title'], 0, 250), 'codes' => $item['codes'] ?? [],
            'series' => $item['series'] ?? null, 'maker' => $item['maker'] ?? null, 'material' => $item['material'] ?? null, 'width_cm' => $item['width_cm'] ?? null,
            'colorways' => $item['colorways'] ?? [], 'price' => $item['price'] ?? null, 'price_unit' => $item['price_unit'] ?? null,
            'price_per_m' => $perM, 'price_type' => $shop->price_type, 'min_order' => $item['min_order'] ?? null,
            'stock' => $item['stock'] ?? null, 'image_urls' => $item['image_urls'] ?? [],
            'raw_path' => "{$file}.{$ext}", 'crawled_at' => now(), 'gone_at' => null,
        ];
        $attrs['content_hash'] = sha1(json_encode([$attrs['title'], $attrs['price'], $attrs['stock'], $attrs['colorways'], $attrs['image_urls']]));

        $p = CrawledProduct::firstOrNew(['shop_id' => $shop->id, 'external_id' => $item['external_id']]);
        if (! $p->exists) {
            $diff['new'][] = $item['external_id'];
        } else {
            if ($p->price_per_m && $perM && $p->price_per_m !== $perM) {
                $diff['price'][] = ['id' => $item['external_id'], 'from' => $p->price_per_m, 'to' => $perM];
            }
            if ($p->stock !== 0 && $attrs['stock'] === 0) {
                $diff['out_of_stock'][] = $item['external_id'];
            } elseif ($p->stock === 0 && $attrs['stock'] !== 0) {
                $diff['back_in_stock'][] = $item['external_id'];
            }
        }
        $imgChanged = ($p->image_urls[0] ?? null) !== ($attrs['image_urls'][0] ?? null) || ! $p->image_path || ! is_file((string) $p->image_path);
        $p->fill($attrs)->save();

        if ($imgChanged && empty($item['no_image_download']) && ($src = $attrs['image_urls'][0] ?? null)) {
            try {
                $path = TenantContext::dir("images/{$shop->code}").'/'.preg_replace('/\W/', '_', $item['external_id']).'.img';
                file_put_contents($path, $this->http->get($src));
                $f = ImageFeatures::fromFile($path);
                $p->update(['image_path' => $path, 'phash' => $f['phash'] ?? null, 'features' => $f]);
                $counts['images']++;
            } catch (ShopBlocked $e) {
                throw $e;
            } catch (\Throwable) {
                $counts['image_errors']++;
            }
        }
    }
}
