<?php

namespace Fujiba\FacebookCatalog\Listeners;

use Fujiba\FacebookCatalog\Jobs\SyncSingleProductJob;
use Fujiba\FacebookCatalog\Models\FacebookCatalogProductSync;
use Fujiba\FacebookCatalog\Models\FacebookCatalogSetting;
use Webkul\Product\Contracts\Product as ProductContract;

/**
 * On any catalog.product.update.after event, dispatch a single-product
 * sync job for every (product, channel) where the product is approved AND
 * the channel's facebook_catalog_settings.auto_sync_price_stock = true.
 *
 * No-op if no approved row exists for the product on any channel — keeps
 * the listener cheap on tenants that haven't enabled the integration.
 */
class OnProductChanged
{
    public function handle($product): void
    {
        $productId = $product instanceof ProductContract ? $product->id : (is_object($product) ? ($product->id ?? null) : null);
        if (! $productId) {
            return;
        }

        $approvedChannels = FacebookCatalogProductSync::where('product_id', $productId)
            ->where('approved', true)
            ->pluck('channel_id');

        if ($approvedChannels->isEmpty()) {
            return;
        }

        $activeAutoSync = FacebookCatalogSetting::whereIn('channel_id', $approvedChannels)
            ->where('is_active', true)
            ->where('auto_sync_price_stock', true)
            ->pluck('channel_id');

        foreach ($activeAutoSync as $channelId) {
            SyncSingleProductJob::dispatch($productId, (int) $channelId)
                ->onQueue((string) config('fujiba-facebook-catalog.queue', 'default'));
        }
    }
}
