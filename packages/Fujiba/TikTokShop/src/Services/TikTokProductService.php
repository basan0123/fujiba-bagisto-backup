<?php

namespace Fujiba\TikTokShop\Services;

use Fujiba\TikTokShop\Models\TikTokCredential;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;
use Webkul\Product\Models\Product;

/**
 * Pushes a Bagisto product to TikTok Shop's catalog.
 *
 * On success: upserts tiktok_product_map with the returned tiktok_product_id,
 * sync_status='synced', last_synced_at=now.
 *
 * On API failure: upserts tiktok_product_map with sync_status='failed' and
 * sync_error populated, but does NOT throw — caller handles the result via
 * the returned status. This keeps batch syncs (Step 7 scheduler) from being
 * derailed by a single product's failure.
 *
 * Category mapping is intentionally a hard failure (RuntimeException) when
 * TIKTOK_DEFAULT_CATEGORY_ID is unset, per master prompt §8. TikTok's API
 * rejects products without a category_id; failing fast at config time
 * surfaces the misconfiguration before TikTok auto-disables the connection.
 */
class TikTokProductService
{
    public function __construct(
        protected TikTokAuthService $authService,
    ) {}

    /**
     * Sync one product. Returns the upserted tiktok_product_map row.
     *
     * The map row is the source of truth for sync state — read sync_status
     * and sync_error to determine success.
     */
    public function syncProduct(int $productId, int $channelId): \Fujiba\TikTokShop\Models\TikTokProductMap
    {
        // Pre-flight log per master prompt §0.8
        Log::info('[TikTokShop] product sync context', [
            'db'         => DB::connection()->getDatabaseName(),
            'tenant'     => function_exists('tenant') && tenant() ? tenant('id') : 'central',
            'product_id' => $productId,
            'channel_id' => $channelId,
        ]);

        $product = Product::findOrFail($productId);

        $creds = TikTokCredential::where('channel_id', $channelId)
            ->where('is_active', true)
            ->first();

        if (! $creds) {
            return $this->markFailed($productId, $channelId, 'No active TikTok credentials for channel ' . $channelId);
        }

        if (! $creds->access_token) {
            return $this->markFailed($productId, $channelId, 'TikTok credentials have no access_token (re-connect required)');
        }

        // Build payload — throws RuntimeException if TIKTOK_DEFAULT_CATEGORY_ID empty
        $payload = $this->buildPayload($product);

        try {
            $response = Http::withToken($creds->access_token)
                ->asJson()
                ->post('https://open-api.tiktokshop.com/product/202309/products', $payload);

            if (! $response->successful()) {
                return $this->markFailed(
                    $productId,
                    $channelId,
                    'TikTok API HTTP ' . $response->status() . ': ' . substr($response->body(), 0, 500)
                );
            }

            // TikTok wraps response in {"code":0, "message":"OK", "data":{...}}
            $body = $response->json();
            if (($body['code'] ?? -1) !== 0) {
                return $this->markFailed(
                    $productId,
                    $channelId,
                    'TikTok API error code=' . ($body['code'] ?? 'null') . ' message=' . ($body['message'] ?? '(none)')
                );
            }

            $tiktokProductId = (string) ($body['data']['product_id'] ?? '');
            if ($tiktokProductId === '') {
                return $this->markFailed(
                    $productId,
                    $channelId,
                    'TikTok API success but no product_id in response: ' . substr($response->body(), 0, 500)
                );
            }

            return $this->markSynced($productId, $channelId, $tiktokProductId);
        } catch (RuntimeException $e) {
            // mapCategory's "config not set" exception bubbles up — it's a hard
            // misconfiguration, not a per-product failure.
            throw $e;
        } catch (Throwable $e) {
            return $this->markFailed($productId, $channelId, 'Exception: ' . $e->getMessage());
        }
    }

    /**
     * @return array{title:string,description:string,category_id:string,images:array,skus:array}
     */
    private function buildPayload(Product $product): array
    {
        return [
            'title'       => (string) ($product->name ?? $product->sku),
            'description' => strip_tags((string) ($product->description ?? '')),
            'category_id' => $this->mapCategory($product),
            'images'      => $this->mapImages($product),
            'skus'        => $this->mapSkus($product),
        ];
    }

    /**
     * Hard fail on missing config. Per master prompt §8 — do not start
     * product sync without TIKTOK_DEFAULT_CATEGORY_ID set.
     */
    private function mapCategory(Product $product): string
    {
        $id = config('fujiba-tiktok.default_category_id');
        if (empty($id)) {
            throw new RuntimeException(
                'TIKTOK_DEFAULT_CATEGORY_ID is not set. Set it in .env before syncing products. '
                . 'See §8 of the TikTok Shop master prompt.'
            );
        }
        return (string) $id;
    }

    /**
     * Returns image URLs in TikTok's expected shape. Step 4 ships a minimal
     * implementation — Bagisto product images live on the `product_images`
     * relation; we'll wire the full image upload flow in a later step.
     *
     * For now: empty array. TikTok's API accepts product creation without
     * images; they can be uploaded as a follow-up call. The smoke test
     * verifies payload SHAPE, not image content.
     */
    private function mapImages(Product $product): array
    {
        return [];
    }

    /**
     * Returns SKU rows in TikTok's expected shape. Step 4 ships a single-SKU
     * implementation — variant/configurable products are out of scope until
     * confirmed against the actual TikTok JP API spec (master prompt §17.3).
     */
    private function mapSkus(Product $product): array
    {
        $price = (float) ($product->price ?? 0);

        return [[
            'seller_sku'   => (string) $product->sku,
            'price'        => [
                'amount'   => number_format($price, 2, '.', ''),
                'currency' => 'JPY',
            ],
            'inventory'    => [[
                'warehouse_id' => '',  // Filled by stock-sync flow in Step 6
                'quantity'     => 0,
            ]],
        ]];
    }

    private function markSynced(int $productId, int $channelId, string $tiktokProductId): \Fujiba\TikTokShop\Models\TikTokProductMap
    {
        return \Fujiba\TikTokShop\Models\TikTokProductMap::updateOrCreate(
            ['bagisto_product_id' => $productId, 'channel_id' => $channelId],
            [
                'tiktok_product_id' => $tiktokProductId,
                'sync_status'       => 'synced',
                'sync_error'        => null,
                'last_synced_at'    => now(),
            ]
        );
    }

    private function markFailed(int $productId, int $channelId, string $error): \Fujiba\TikTokShop\Models\TikTokProductMap
    {
        Log::warning('[TikTokShop] product sync failed', [
            'product_id' => $productId,
            'channel_id' => $channelId,
            'error'      => $error,
        ]);

        return \Fujiba\TikTokShop\Models\TikTokProductMap::updateOrCreate(
            ['bagisto_product_id' => $productId, 'channel_id' => $channelId],
            [
                // tiktok_product_id is NOT NULL on the table, so we keep any
                // prior value (re-sync of a previously-synced product) or
                // store an empty sentinel for first-ever-failed.
                'tiktok_product_id' => DB::raw("COALESCE(tiktok_product_id, '')"),
                'sync_status'       => 'failed',
                'sync_error'        => $error,
                'last_synced_at'    => now(),
            ]
        );
    }
}
