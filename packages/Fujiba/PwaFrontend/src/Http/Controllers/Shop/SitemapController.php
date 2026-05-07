<?php

namespace Fujiba\PwaFrontend\Http\Controllers\Shop;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Webkul\Category\Models\Category;
use Webkul\CMS\Models\Page;
use Webkul\Product\Models\ProductFlat;

class SitemapController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $channel = core()->getCurrentChannel();
        // Cache per (host, channel code) so kimono.fujiba.co.jp and
        // mmfood.fujiba.co.jp don't share a single cached payload.
        $cacheKey = 'fujiba.sitemap.xml.'.$request->getHttpHost().'.'.$channel->code;

        $xml = Cache::remember($cacheKey, now()->addHour(), function () use ($channel, $request) {
            $base    = $this->resolveBaseUrl($channel, $request);
            $locales = $channel->locales->pluck('code')->all();

            $urls = [];
            $urls[] = $this->entry($base.'/', $locales, 'daily', '1.0');

            // Categories: only those that have at least one product on the
            // current channel. Without this filter a multi-channel tenant
            // (e.g. kimono + mmfood) leaks cross-channel URLs into each
            // host's sitemap.
            $channelCategoryIds = ProductFlat::query()
                ->where('channel', $channel->code)
                ->where('locale', $channel->default_locale->code ?? 'en')
                ->where('status', 1)
                ->where('visible_individually', 1)
                ->join('product_categories', 'product_categories.product_id', '=', 'product_flat.product_id')
                ->distinct()
                ->pluck('product_categories.category_id')
                ->all();

            if (! empty($channelCategoryIds)) {
                Category::query()
                    ->whereIn('id', $channelCategoryIds)
                    ->where('status', 1)
                    ->whereNotNull('parent_id')
                    ->with('translations')
                    ->each(function ($category) use (&$urls, $base, $locales) {
                        $slug = $category->translations->firstWhere('locale', config('app.fallback_locale', 'en'))?->slug
                            ?? $category->translations->first()?->slug;
                        if (! $slug) return;
                        $urls[] = $this->entry($base.'/'.$slug, $locales, 'weekly', '0.8');
                    });
            }

            $defaultLocale = optional($channel->default_locale)->code
                ?? config('app.fallback_locale', 'en');
            ProductFlat::query()
                ->where('channel', $channel->code)
                ->where('locale', $defaultLocale)
                ->where('status', 1)
                ->where('visible_individually', 1)
                ->whereNotNull('url_key')
                ->select('product_id', 'url_key')
                ->chunk(500, function ($rows) use (&$urls, $base, $locales) {
                    foreach ($rows as $r) {
                        $urls[] = $this->entry($base.'/'.$r->url_key, $locales, 'weekly', '0.7');
                    }
                });

            $channelId = $channel->id;
            Page::query()
                ->whereHas('channels', fn ($q) => $q->where('channels.id', $channelId))
                ->with('translations')
                ->each(function ($page) use (&$urls, $base, $locales) {
                    $slug = $page->translations->firstWhere('locale', config('app.fallback_locale', 'en'))?->url_key
                        ?? $page->translations->first()?->url_key;
                    if (! $slug) return;
                    $urls[] = $this->entry($base.'/page/'.$slug, $locales, 'monthly', '0.5');
                });

            return view('pwa-frontend::seo.sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
    }

    /**
     * Build a `https://host` base URL. Some channels (tenant-side) store the
     * hostname WITHOUT a scheme (e.g. "kimono.fujiba.co.jp"); central stored
     * "https://snssale.com/". Normalize both to a scheme + host string with
     * no trailing slash.
     */
    private function resolveBaseUrl($channel, Request $request): string
    {
        $hostname = (string) ($channel->hostname ?: $request->getHttpHost());
        $hostname = trim($hostname, " \t\n\r/");

        if (! preg_match('#^https?://#i', $hostname)) {
            $hostname = $request->getScheme().'://'.$hostname;
        }

        return rtrim($hostname, '/');
    }

    private function entry(string $url, array $locales, string $changefreq, string $priority): array
    {
        $encoded = $this->encodeUrl($url);

        return [
            'loc'        => $encoded,
            'changefreq' => $changefreq,
            'priority'   => $priority,
            'alternates' => array_map(fn ($code) => [
                'hreflang' => $code,
                'href'     => $encoded.(str_contains($encoded, '?') ? '&' : '?').'locale='.$code,
            ], $locales),
        ];
    }

    /**
     * Per the sitemap spec each URL must be a valid IRI converted to a URI:
     * non-ASCII path segments get percent-encoded, but `/` and `:` stay raw.
     * `urlencode()` mangles slashes, so we split on `/` and rejoin.
     */
    private function encodeUrl(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false) return $url;

        $scheme = isset($parts['scheme']) ? $parts['scheme'].'://' : '';
        $host   = $parts['host'] ?? '';
        $port   = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path   = $parts['path'] ?? '';
        $query  = isset($parts['query']) ? '?'.$parts['query'] : '';

        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $path)));

        return $scheme.$host.$port.$encodedPath.$query;
    }
}
