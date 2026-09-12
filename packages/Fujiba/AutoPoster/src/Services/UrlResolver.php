<?php

namespace Fujiba\AutoPoster\Services;

use Fujiba\FacebookCatalog\Services\ProductPayloadBuilder;
use Illuminate\Support\Facades\DB;

/**
 * Image / product / Messenger URLs for a post.
 *
 * The storefront-host rule is NOT reimplemented here — it is a hard-won
 * constraint (Meta fetches these URLs itself; APP_URL-derived paths 404 for
 * tenant media and surface as "Image fetch failed"). ProductPayloadBuilder owns
 * it, and this class subclasses it purely to reach its protected helpers, so
 * there is exactly one definition of how a public URL is built.
 */
class UrlResolver extends ProductPayloadBuilder
{
    public function imageUrl(int $productId, int $channelId): ?string
    {
        return $this->resolveImageUrl($productId, $channelId);
    }

    public function productUrlFor(int $productId, int $channelId, string $locale = 'ja'): string
    {
        $urlKey = DB::table('product_flat')
            ->where('product_id', $productId)
            ->where('locale', $locale)
            ->orderByDesc('id')
            ->value('url_key');

        if (! $urlKey) {
            $urlKey = DB::table('product_flat')
                ->where('product_id', $productId)
                ->orderByDesc('id')
                ->value('url_key');
        }

        return $this->productUrl($urlKey, $channelId);
    }

    public function storefrontBaseUrl(int $channelId): string
    {
        return $this->channelBaseUrl($channelId);
    }

    /**
     * The Messenger CTA link.
     *
     * m.me/<page-id> opens the page's Messenger thread. The optional ref is
     * echoed back by Messenger as a referral payload, which is how post context
     * could later be carried into the conversation — note that the existing
     * ChatPion auto-flow dispatcher does NOT read ref today (it polls on
     * conversation freshness), so this is attribution groundwork, not a live
     * hook. Do not assume ChatPion will branch on it without further work.
     */
    public function messengerUrl(string $pageId, ?string $ref = null): string
    {
        $url = 'https://m.me/' . ltrim(trim($pageId), '/');

        if ($ref !== null && trim($ref) !== '') {
            $url .= '?ref=' . rawurlencode(trim($ref));
        }

        return $url;
    }
}
