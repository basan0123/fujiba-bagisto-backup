<?php

namespace Fujiba\FacebookCatalog\Services;

use Fujiba\FacebookCatalog\Models\FacebookCatalogSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
    public function build(Product $product, FacebookCatalogSetting $settings): ?array
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

        $stock = (int) DB::table('product_inventories')
            ->where('product_id', $product->id)
            ->sum('qty');

        $availability = $stock > 0 ? 'in stock' : 'out of stock';

        $price = $flat->special_price && $flat->special_price > 0 && $flat->special_price < $flat->price
            ? $flat->special_price
            : $flat->price;

        $imageUrl = $this->resolveImageUrl($product->id);

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
            'inventory'    => $stock,
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

    protected function resolveImageUrl(int $productId): ?string
    {
        $image = DB::table('product_images')
            ->where('product_id', $productId)
            ->orderBy('id')
            ->first();

        if (! $image || empty($image->path)) {
            return null;
        }

        return Storage::disk('public')->url($image->path);
    }

    protected function productUrl(?string $urlKey, int $channelId): string
    {
        if (! $urlKey) {
            return url('/');
        }

        return url('/products/' . ltrim($urlKey, '/'));
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
