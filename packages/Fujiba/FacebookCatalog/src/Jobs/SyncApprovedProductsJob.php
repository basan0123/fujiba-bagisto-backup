<?php

namespace Fujiba\FacebookCatalog\Jobs;

use Fujiba\FacebookCatalog\Services\BatchSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncApprovedProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600;

    public function __construct(public int $channelId) {}

    public function handle(BatchSyncService $service): void
    {
        $result = $service->syncChannel($this->channelId);

        Log::info('[FacebookCatalog] sync job done', [
            'channel_id' => $this->channelId,
            'result'     => $result,
        ]);
    }
}
