<?php

namespace Fujiba\TikTokShop\Listeners;

use Fujiba\TikTokShop\Jobs\SyncStockToTikTok;
use Fujiba\TikTokShop\Models\TikTokProductMap;
use Illuminate\Support\Facades\Log;
use Webkul\Product\Contracts\Product as ProductContract;
use Webkul\Product\Models\Product;

/**
 * Listens to catalog.product.update.after (and .create.after) to push
 * inventory changes into TikTok Shop.
 *
 * Step 6 design note (see SPRINT_2_DISCOVERY.md item G):
 * Bagisto does NOT fire a dedicated stock event. The
 * `catalog.product.update.after` event is the only signal — but it fires
 * for both metadata edits AND for inventory edits. The inventory-update
 * admin endpoint writes to product_inventories, then dispatches
 * catalog.product.update.after with the Product model — which has NOT
 * itself changed, so $product->wasChanged() returns false for stock
 * updates.
 *
 * Therefore the master prompt's `wasChanged(['quantity', ...])` fallback
 * does NOT work in this Bagisto build (would produce false negatives —
 * stock-only changes silently skipped). Instead: dispatch a stock sync
 * unconditionally for every product-update event, but only when the
 * product has a tiktok_product_map row. The job itself is cheap (one DB
 * read + one TikTok API call) and the over-dispatch is bounded to
 * already-synced products.
 *
 * Product sync (catalog metadata push) is wired separately in Step 4 —
 * NOT auto-dispatched on every product update yet. Step 7 scheduler is
 * the canonical product sync trigger; admins also manually trigger via
 * the "Sync Products Now" button.
 */
class OnProductChanged
{
    /**
     * Handle the event. Bagisto passes either a Product model or an int id.
     */
    public function handle($eventArg): void
    {
        $product = $this->normaliseProduct($eventArg);
        if (! $product) {
            return;
        }

        // Only dispatch if there's an existing map row — otherwise the
        // product hasn't been synced to TikTok yet and a stock push has
        // nothing to update against.
        $hasMap = TikTokProductMap::where('bagisto_product_id', $product->id)->exists();
        if (! $hasMap) {
            return;
        }

        $tenantId = function_exists('tenant') && tenant() ? (string) tenant('id') : null;
        if (! $tenantId) {
            Log::warning('[TikTokShop] OnProductChanged fired without tenancy initialized', [
                'product_id' => $product->id,
            ]);
            return;
        }

        SyncStockToTikTok::dispatch($tenantId, $product->id)->onQueue('tiktok');
    }

    private function normaliseProduct($eventArg): ?Product
    {
        if ($eventArg instanceof ProductContract) {
            return $eventArg;
        }
        if (is_object($eventArg) && method_exists($eventArg, 'getKey')) {
            return $eventArg;
        }
        if (is_int($eventArg) || is_string($eventArg)) {
            return Product::find((int) $eventArg);
        }
        return null;
    }
}
