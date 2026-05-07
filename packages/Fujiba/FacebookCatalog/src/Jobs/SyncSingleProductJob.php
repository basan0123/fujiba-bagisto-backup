<?php

namespace Fujiba\FacebookCatalog\Jobs;

use Fujiba\FacebookCatalog\Models\FacebookCatalogSetting;
use Fujiba\FacebookCatalog\Services\BatchSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Pushes a single (product, channel) update to Facebook. Used by the
 * price/stock change listener so individual edits don't have to wait for a
 * bulk sync run.
 */
class SyncSingleProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(public int $productId, public int $channelId) {}

    public function handle(BatchSyncService $service): void
    {
        $settings = FacebookCatalogSetting::where('channel_id', $this->channelId)
            ->where('is_active', true)
            ->first();

        if (! $settings || ! $settings->auto_sync_price_stock) {
            return;
        }

        $service->syncProducts($settings, [$this->productId]);
    }
}
