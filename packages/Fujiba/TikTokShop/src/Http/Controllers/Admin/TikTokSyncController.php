<?php

namespace Fujiba\TikTokShop\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Fujiba\TikTokShop\Models\TikTokCredential;
use Fujiba\TikTokShop\Models\TikTokProductMap;
use Fujiba\TikTokShop\Services\TikTokProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;
use Webkul\Product\Models\Product;

class TikTokSyncController extends Controller
{
    public function __construct(
        protected TikTokProductService $productService,
    ) {}

    /**
     * POST /admin/tiktok/sync/products
     * Body: { channel_id: int }
     *
     * Step 4 ships the manual single-channel batch sync. Step 7 wires
     * the scheduler to dispatch one job per tenant; that path will call
     * this same service.
     *
     * Hard pre-flight: if TIKTOK_DEFAULT_CATEGORY_ID is empty, do not
     * attempt the loop — the service throws on first call anyway, but
     * surfacing the error here keeps the admin UX clean.
     */
    public function products(Request $request)
    {
        $channelId = (int) $request->input('channel_id', 0);

        if ($channelId <= 0) {
            return back()->with('error', 'Invalid channel_id.');
        }

        if (empty(config('fujiba-tiktok.default_category_id'))) {
            return back()->with('error',
                'TIKTOK_DEFAULT_CATEGORY_ID is not set. Configure .env before syncing products. See §8 of master prompt.'
            );
        }

        $creds = TikTokCredential::where('channel_id', $channelId)
            ->where('is_active', true)
            ->where('sync_products', true)
            ->first();

        if (! $creds) {
            return back()->with('error', 'No active TikTok connection (with sync_products enabled) for this channel.');
        }

        // Step 4 scope: sync products that exist on this channel via product_flat.
        // The product_flat table is the channel-scoped read source in this
        // Bagisto build (verified during Step 1 schema introspection).
        $productIds = \DB::table('product_flat')
            ->where('channel', \Webkul\Core\Models\Channel::find($channelId)->code)
            ->pluck('product_id')
            ->unique()
            ->values()
            ->all();

        $results = ['synced' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($productIds as $productId) {
            try {
                $map = $this->productService->syncProduct((int) $productId, $channelId);
                if ($map->sync_status === 'synced') {
                    $results['synced']++;
                } else {
                    $results['failed']++;
                }
            } catch (RuntimeException $e) {
                // Hard config error — abort the whole batch.
                return back()->with('error', $e->getMessage());
            } catch (Throwable $e) {
                Log::error('[TikTokShop] sync loop unexpected error', [
                    'product_id' => $productId,
                    'message'    => $e->getMessage(),
                ]);
                $results['skipped']++;
            }
        }

        $creds->last_synced_at = now();
        $creds->save();

        return back()->with('success', sprintf(
            'Sync complete: %d synced, %d failed, %d skipped.',
            $results['synced'], $results['failed'], $results['skipped']
        ));
    }

    /**
     * POST /admin/tiktok/sync/products/retry-failed
     * Body: { channel_id: int }
     *
     * Re-syncs only products with sync_status='failed' for the given channel.
     * Same pre-flight guards as products(); same per-product behaviour.
     */
    public function retryFailed(Request $request)
    {
        $channelId = (int) $request->input('channel_id', 0);

        if ($channelId <= 0) {
            return back()->with('error', 'Invalid channel_id.');
        }

        if (empty(config('fujiba-tiktok.default_category_id'))) {
            return back()->with('error',
                'TIKTOK_DEFAULT_CATEGORY_ID is not set. Configure .env before retrying. See §8 of master prompt.'
            );
        }

        $creds = TikTokCredential::where('channel_id', $channelId)
            ->where('is_active', true)
            ->where('sync_products', true)
            ->first();

        if (! $creds) {
            return back()->with('error', 'No active TikTok connection (with sync_products enabled) for this channel.');
        }

        $failedProductIds = TikTokProductMap::where('channel_id', $channelId)
            ->where('sync_status', 'failed')
            ->pluck('bagisto_product_id')
            ->all();

        if (empty($failedProductIds)) {
            return back()->with('success', 'No failed products to retry.');
        }

        $results = ['synced' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($failedProductIds as $productId) {
            try {
                $map = $this->productService->syncProduct((int) $productId, $channelId);
                $map->sync_status === 'synced' ? $results['synced']++ : $results['failed']++;
            } catch (RuntimeException $e) {
                return back()->with('error', $e->getMessage());
            } catch (Throwable $e) {
                Log::error('[TikTokShop] retry loop unexpected error', [
                    'product_id' => $productId,
                    'message'    => $e->getMessage(),
                ]);
                $results['skipped']++;
            }
        }

        return back()->with('success', sprintf(
            'Retry complete: %d recovered, %d still failing, %d skipped.',
            $results['synced'], $results['failed'], $results['skipped']
        ));
    }
}
