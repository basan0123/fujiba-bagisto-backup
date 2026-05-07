<?php

namespace Fujiba\TikTokShop\Jobs;

use App\Models\Tenant;
use Fujiba\TikTokShop\Models\TikTokCredential;
use Fujiba\TikTokShop\Models\TikTokProductMap;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pushes a single product's current inventory qty to TikTok Shop.
 *
 * Dispatched by Fujiba\TikTokShop\Listeners\OnProductChanged on
 * catalog.product.update.after. Tenant context is captured at dispatch
 * time and re-initialized when the job runs (queue worker context).
 *
 * Capped at 3 attempts per master prompt + Step 6 instructions —
 * persistent TikTok API failures should not retry indefinitely.
 *
 * Channel resolution: for each tiktok_product_map row matching the
 * product, look up that channel's credentials and push qty independently.
 * A product synced to multiple channels in tiktok_product_map gets one
 * PATCH per channel.
 */
class SyncStockToTikTok implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30; // seconds between retries

    public function __construct(
        public string $tenantId,
        public int $bagistoProductId,
    ) {}

    public function handle(): void
    {
        $tenant = Tenant::find($this->tenantId);
        if (! $tenant) {
            Log::warning('[TikTokShop] stock sync tenant not found, dropping job', [
                'tenant_id' => $this->tenantId,
            ]);
            return;
        }

        tenancy()->initialize($tenant);

        try {
            Log::info('[TikTokShop] stock sync context', [
                'db'         => DB::connection()->getDatabaseName(),
                'tenant'     => function_exists('tenant') && tenant() ? tenant('id') : 'central',
                'product_id' => $this->bagistoProductId,
            ]);

            $maps = TikTokProductMap::where('bagisto_product_id', $this->bagistoProductId)->get();
            if ($maps->isEmpty()) {
                Log::info('[TikTokShop] stock sync: product not in tiktok_product_map, skipping', [
                    'product_id' => $this->bagistoProductId,
                ]);
                return;
            }

            // Sum qty across all inventory sources for this product.
            // Inventory lives in product_inventories (qty INT, one row per
            // (product_id, inventory_source_id) tuple). TikTok cares about
            // total available units — sum is the right aggregation here
            // until a future step wires per-warehouse inventory mapping.
            $qty = (int) DB::table('product_inventories')
                ->where('product_id', $this->bagistoProductId)
                ->sum('qty');

            foreach ($maps as $map) {
                $this->pushOne($map, $qty);
            }
        } finally {
            tenancy()->end();
        }
    }

    private function pushOne(TikTokProductMap $map, int $qty): void
    {
        $creds = TikTokCredential::where('channel_id', $map->channel_id)
            ->where('is_active', true)
            ->where('sync_stock', true)
            ->first();

        if (! $creds || ! $creds->access_token) {
            Log::info('[TikTokShop] stock sync: no active credentials for channel, skipping', [
                'channel_id' => $map->channel_id,
                'product_id' => $this->bagistoProductId,
            ]);
            return;
        }

        try {
            $response = Http::withToken($creds->access_token)
                ->asJson()
                ->patch(
                    'https://open-api.tiktokshop.com/product/202309/products/' . $map->tiktok_product_id . '/inventory',
                    [
                        'skus' => [[
                            'id'        => $map->tiktok_product_id,  // Single-SKU placeholder per Step 4 §17.3
                            'inventory' => [[
                                'warehouse_id' => '',
                                'quantity'     => $qty,
                            ]],
                        ]],
                    ]
                );

            if (! $response->successful()) {
                $this->markFailed($map, 'HTTP ' . $response->status() . ': ' . substr($response->body(), 0, 500));
                return;
            }

            $body = $response->json();
            if (($body['code'] ?? -1) !== 0) {
                $this->markFailed($map, 'API code=' . ($body['code'] ?? 'null') . ' message=' . ($body['message'] ?? '(none)'));
                return;
            }

            $map->update([
                'sync_status'    => 'synced',
                'sync_error'    => null,
                'last_synced_at' => now(),
            ]);

            Log::info('[TikTokShop] stock synced', [
                'product_id'        => $this->bagistoProductId,
                'channel_id'        => $map->channel_id,
                'tiktok_product_id' => $map->tiktok_product_id,
                'qty'               => $qty,
            ]);
        } catch (Throwable $e) {
            $this->markFailed($map, 'Exception: ' . $e->getMessage());
            throw $e; // surface to queue retry machinery (capped at $tries)
        }
    }

    private function markFailed(TikTokProductMap $map, string $error): void
    {
        Log::warning('[TikTokShop] stock sync failed', [
            'product_id'        => $this->bagistoProductId,
            'channel_id'        => $map->channel_id,
            'tiktok_product_id' => $map->tiktok_product_id,
            'error'             => $error,
            'attempt'           => $this->attempts(),
        ]);

        $map->update([
            'sync_status' => 'failed',
            'sync_error'  => 'stock: ' . $error,
        ]);
    }
}
