<?php

namespace App\Jobs\Sagawa;

use App\Events\Sagawa\WaybillFailed;
use Fujiba\SagawaDelivery\Models\Shipment;
use Fujiba\SagawaDelivery\Services\ShipmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Issues the Sagawa waybill for a verified shipment and schedules the PDF poll.
 */
class IssueWaybillJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public readonly int $shipmentId) {}

    public function handle(ShipmentService $service): void
    {
        $shipment = Shipment::find($this->shipmentId);
        if (! $shipment) {
            return;
        }

        if (! in_array($shipment->status, [
            Shipment::STATUS_ADDRESS_VERIFIED,
            Shipment::STATUS_REISSUE_REQUESTED,
        ], true)) {
            return;
        }

        try {
            $result = $service->issueWaybill($shipment, printOut: true);
        } catch (\Throwable $e) {
            $shipment->markWaybillFailed($e->getMessage());
            WaybillFailed::dispatch($shipment, null, $e->getMessage());

            return;
        }

        $code    = (string) ($result['result_code'] ?? '');
        $parsed  = $result['parsed'] ?? [];
        $printId = $this->findScalar($parsed, 'printRequestId');

        $shipment->update([
            'sagawa_raw_response' => $result['response'] ?? null,
        ]);

        if (str_starts_with($code, 'S')) {
            $shipment->markWaybillPending($printId);
            PollWaybillPdfJob::dispatch($shipment->id)
                ->delay(now()->addSeconds((int) config('sagawa.pdf_poll_interval', 30)));

            return;
        }

        $shipment->markWaybillFailed($code ?: 'unknown error');
        WaybillFailed::dispatch($shipment, $code ?: null, $code ?: 'unknown error');
    }

    /** @param  array<string,mixed>  $parsed */
    private function findScalar(array $parsed, string $key): ?string
    {
        $found = null;
        array_walk_recursive($parsed, function ($value, $k) use (&$found, $key) {
            if (! $found && is_string($k) && $k === $key && is_scalar($value)) {
                $found = (string) $value;
            }
        });

        return $found;
    }
}
