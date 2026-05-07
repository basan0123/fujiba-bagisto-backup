<?php

namespace Fujiba\FacebookCatalog\Services;

use Fujiba\FacebookCatalog\Models\FacebookCatalogProductSync;
use Fujiba\FacebookCatalog\Models\FacebookCatalogSetting;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Product\Models\Product;

class BatchSyncService
{
    public function __construct(
        protected ProductPayloadBuilder $builder,
    ) {}

    /**
     * Push every approved product for the given channel to Facebook.
     *
     * Returns ['sent' => int, 'failed' => int, 'skipped' => int].
     */
    public function syncChannel(int $channelId): array
    {
        $settings = FacebookCatalogSetting::where('channel_id', $channelId)
            ->where('is_active', true)
            ->first();

        if (! $settings) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'reason' => 'channel not active'];
        }

        $approvedIds = FacebookCatalogProductSync::where('channel_id', $channelId)
            ->where('approved', true)
            ->pluck('product_id')
            ->all();

        if (empty($approvedIds)) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'reason' => 'no approved products'];
        }

        return $this->syncProducts($settings, $approvedIds);
    }

    /**
     * Push a specific list of product ids. Skips ids without an approved row.
     */
    public function syncProducts(FacebookCatalogSetting $settings, array $productIds): array
    {
        $batchSize = (int) config('fujiba-facebook-catalog.batch_size', 200);
        $client    = new GraphClient($settings->access_token);

        $sent = $failed = $skipped = 0;
        $chunks = array_chunk(array_values(array_unique($productIds)), $batchSize);

        foreach ($chunks as $chunk) {
            $requests = [];
            $rowsByRetailer = [];

            $products = Product::whereIn('id', $chunk)->get()->keyBy('id');
            $rows = FacebookCatalogProductSync::where('channel_id', $settings->channel_id)
                ->whereIn('product_id', $chunk)
                ->where('approved', true)
                ->get()
                ->keyBy('product_id');

            foreach ($chunk as $productId) {
                $product = $products->get($productId);
                $row     = $rows->get($productId);

                if (! $product || ! $row) {
                    $skipped++;
                    continue;
                }

                $payload = $this->builder->build($product, $settings);
                if (! $payload) {
                    $row->sync_status = FacebookCatalogProductSync::STATUS_FAILED;
                    $row->sync_error  = 'no image or product_flat row';
                    $row->save();
                    $failed++;
                    continue;
                }

                $requests[] = $payload;
                $rowsByRetailer[$payload['data']['id']] = $row;
            }

            if (empty($requests)) {
                continue;
            }

            try {
                $response = $client->postItemsBatch($settings->catalog_id, $requests);
                $sent += count($requests);

                $now = now();
                foreach ($rowsByRetailer as $retailerId => $row) {
                    $row->sync_status      = FacebookCatalogProductSync::STATUS_SYNCED;
                    $row->fb_retailer_id   = $retailerId;
                    $row->sync_error       = null;
                    $row->last_payload_hash = $this->hashRequest($requests);
                    $row->last_synced_at   = $now;
                    $row->save();
                }

                Log::info('[FacebookCatalog] batch sent', [
                    'channel_id' => $settings->channel_id,
                    'count'      => count($requests),
                    'handles'    => $response['handles'] ?? null,
                ]);
            } catch (Throwable $e) {
                $failed += count($requests);

                foreach ($rowsByRetailer as $row) {
                    $row->sync_status = FacebookCatalogProductSync::STATUS_FAILED;
                    $row->sync_error  = mb_substr($e->getMessage(), 0, 1000);
                    $row->save();
                }

                Log::warning('[FacebookCatalog] batch failed', [
                    'channel_id' => $settings->channel_id,
                    'count'      => count($requests),
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        $settings->last_synced_at = now();
        $settings->last_sync_error = $failed > 0 ? "Last run had {$failed} failure(s)." : null;
        $settings->save();

        return ['sent' => $sent, 'failed' => $failed, 'skipped' => $skipped];
    }

    protected function hashRequest(array $requests): string
    {
        return substr(hash('sha256', json_encode($requests)), 0, 32);
    }
}
