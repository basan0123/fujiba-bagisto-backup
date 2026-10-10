<?php

namespace Fujiba\FacebookCatalog\Services;

use Fujiba\FacebookCatalog\Models\FacebookCatalogSetting;
use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;

/**
 * Builds Graph API items_batch request entries from Bagisto products.
 *
 * Output shape per product:
 *   { method: 'UPDATE', data: { id, title, description, ... } }
 *
 * `id` is the Facebook retailer_id — we use the Bagisto product SKU since it
 * is unique per tenant and stable. Falls back to `bagisto-{product_id}` if SKU
 * is empty.
 */
class ProductPayloadBuilder
{
    public function build(Product $product, FacebookCatalogSetting $settings, bool $forceInactive = false): ?array
    {
        $flat = DB::table('product_flat')
            ->where('product_id', $product->id)
            ->where('channel', $this->channelCode($settings->channel_id))
            ->where('locale', $settings->default_locale ?: 'ja')
            ->orderByDesc('id')
            ->first();

        if (! $flat) {
            $flat = DB::table('product_flat')
                ->where('product_id', $product->id)
                ->orderByDesc('id')
                ->first();
        }

        if (! $flat) {
            return null;
        }

        $retailerId = $product->sku ?: ('bagisto-' . $product->id);

        // Sellable stock = on hand minus units reserved by open orders (what
        // the storefront itself allows to be bought). Raw qty kept showing
        // "in stock" ads for kimono whose last pieces were already ordered.
        $onHand = (int) DB::table('product_inventories')
            ->where('product_id', $product->id)
            ->sum('qty');

        $reserved = (int) DB::table('product_ordered_inventories')
            ->where('product_id', $product->id)
            ->where('channel_id', $settings->channel_id)
            ->sum('qty');

        $stock = max(0, $onHand - $reserved);

        // Disabled / hidden products are pushed too (as archived) so Meta drops
        // them from ads instead of keeping their last "in stock" copy.
        $sellable = $stock > 0 && ! $forceInactive && (int) $flat->status === 1;

        $availability = $sellable ? 'in stock' : 'out of stock';

        $price = $flat->special_price && $flat->special_price > 0 && $flat->special_price < $flat->price
            ? $flat->special_price
            : $flat->price;

        $imageUrl = $this->resolveImageUrl($product->id, $settings->channel_id);

        $data = [
            'id'           => $retailerId,
            'title'        => $this->truncate($flat->name ?: ('Product ' . $product->id), 150),
            'description'  => $this->truncate(
                strip_tags((string) ($flat->description ?: $flat->short_description ?: $flat->name)),
                5000
            ),
            'availability' => $availability,
            'condition'    => 'new',
            'price'        => $this->formatPrice($price, $settings->default_currency),
            'link'         => $this->productUrl($flat->url_key, $settings->channel_id),
            'image_link'   => $imageUrl,
            'brand'        => config('app.name', 'Fujiba'),
            'inventory'    => $sellable ? $stock : 0,
            // Only active items can appear in ads / shops; out of stock = archived.
            'status'       => $sellable ? 'active' : 'archived',
        ];

        if (empty($imageUrl)) {
            return null;
        }

        return [
            'method' => 'UPDATE',
            'data'   => array_filter($data, fn ($v) => $v !== null && $v !== ''),
        ];
    }

    public function buildDelete(string $retailerId): array
    {
        return [
            'method' => 'DELETE',
            'data'   => ['id' => $retailerId],
        ];
    }

    protected function channelCode(int $channelId): string
    {
        $row = DB::table('channels')->where('id', $channelId)->first();

        return $row?->code ?? 'default';
    }

    protected function resolveImageUrl(int $productId, int $channelId): ?string
    {
        $image = DB::table('product_images')
            ->where('product_id', $productId)
            ->orderBy('position')   // same "main image" the storefront shows
            ->orderBy('id')
            ->first();

        if (! $image || empty($image->path)) {
            return null;
        }

        // Same constraint as productUrl(): Facebook fetches this URL directly,
        // so it must point at the channel's own storefront host. Storage::url()
        // builds from APP_URL (snssale.com), where /storage/* resolves against
        // the CENTRAL disk and 404s — tenant media only exists under the tenant
        // host, which returns HTML rather than an image and surfaces on Meta as
        // "Image fetch failed".
        $path = ltrim((string) $image->path, '/');
        $base = rtrim($this->channelBaseUrl($channelId), '/');

        // Supplier-synced images are stored as WebP with a JPG sibling
        // (Fujiba\SupplierImageSync). Meta's catalog is most reliable with JPEG.
        if (str_ends_with($path, '.webp')) {
            $jpg = substr($path, 0, -5).'.jpg';
            $root = storage_path('app/public/tenants/'.$channelId.'/'.tenancy()->tenant?->getTenantKey());

            if (tenancy()->initialized && is_file($root.'/'.$jpg)) {
                $path = $jpg;
            }
        }

        return $base . '/storage/' . $path;
    }

    protected function productUrl(?string $urlKey, int $channelId): string
    {
        // Facebook fetches this link directly, so it must point at the channel's
        // own storefront host (e.g. kimono.fujiba.co.jp) — not the global APP_URL
        // (snssale.com), where the storefront isn't served.
        //
        // The slug sits at the ROOT, not under /products/: Bagisto resolves
        // products and categories through Route::fallback() in
        // Webkul/Shop/src/Routes/store-front-routes.php, so /products/{slug}
        // has no matching route and renders the 404 page — which is what ad
        // clicks were landing on.
        $base = rtrim($this->channelBaseUrl($channelId), '/');

        if (! $urlKey) {
            return $base . '/';
        }

        return $base . '/' . ltrim($urlKey, '/');
    }

    protected function channelBaseUrl(int $channelId): string
    {
        $hostname = DB::table('channels')->where('id', $channelId)->value('hostname');
        $hostname = trim((string) $hostname);

        if ($hostname === '') {
            return rtrim((string) config('app.url'), '/');
        }

        // hostname may be stored bare ("kimono.fujiba.co.jp") or with a scheme.
        if (! preg_match('#^https?://#i', $hostname)) {
            $hostname = 'https://' . $hostname;
        }

        return rtrim($hostname, '/');
    }

    protected function formatPrice(float|string|null $price, string $currency): string
    {
        $amount = number_format((float) $price, 2, '.', '');

        return $amount . ' ' . strtoupper($currency);
    }

    protected function truncate(string $value, int $max): string
    {
        if (mb_strlen($value) <= $max) {
            return $value;
        }

        return mb_substr($value, 0, $max - 1) . '…';
    }
}
