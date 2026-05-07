<?php

namespace Fujiba\InventoryControl\Jobs;

use Fujiba\InventoryControl\Models\StockTakeSession;
use Fujiba\InventoryControl\Services\StockTakeBaselineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SnapshotBaselineJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(public int $sessionId) {}

    public function handle(StockTakeBaselineService $service): void
    {
        $session = StockTakeSession::find($this->sessionId);

        if (! $session) {
            Log::warning('SnapshotBaselineJob: session missing', ['session_id' => $this->sessionId]);
            return;
        }

        // Already finalized — no-op so retries after partial state don't double-snapshot.
        if ($session->baseline_status === StockTakeSession::BASELINE_READY) {
            return;
        }

        $service->snapshot($session);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SnapshotBaselineJob failed', [
            'session_id' => $this->sessionId,
            'error'      => $e->getMessage(),
        ]);

        StockTakeSession::where('id', $this->sessionId)->update([
            'baseline_status' => StockTakeSession::BASELINE_NONE,
        ]);
    }
}
