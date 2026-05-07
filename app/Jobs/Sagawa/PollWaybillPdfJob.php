<?php

namespace App\Jobs\Sagawa;

use App\Events\Sagawa\WaybillFailed;
use App\Events\Sagawa\WaybillIssued;
use Carbon\Carbon;
use Fujiba\SagawaDelivery\Models\Shipment;
use Fujiba\SagawaDelivery\Services\CredentialResolver;
use Fujiba\SagawaDelivery\Services\SagawaClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Polls Sagawa checkfile for a ready PDF. Re-dispatches itself with a delay
 * until the PDF is ready or sagawa.pdf_poll_max_attempts is reached.
 */
class PollWaybillPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // re-dispatching handles its own retry pacing

    public function __construct(public readonly int $shipmentId) {}

    public function handle(CredentialResolver $resolver): void
    {
        $shipment = Shipment::find($this->shipmentId);
        if (! $shipment || $shipment->status !== Shipment::STATUS_WAYBILL_PENDING) {
            return;
        }

        $printId = $shipment->print_request_id;
        if (! $printId) {
            $shipment->markWaybillFailed('missing print_request_id');
            WaybillFailed::dispatch($shipment, null, 'missing print_request_id');

            return;
        }

        $max = max(1, (int) config('sagawa.pdf_poll_max_attempts', 20));

        if ($shipment->pdf_poll_attempts >= $max) {
            $shipment->markWaybillFailed('pdf poll max attempts exceeded');
            WaybillFailed::dispatch($shipment, null, 'pdf poll max attempts exceeded');

            return;
        }

        $shipment->increment('pdf_poll_attempts');

        try {
            $credential = $resolver->for(
                tenantId: function_exists('tenant') ? tenant('id') : null,
                channelId: $shipment->channel_id,
            );
        } catch (\Throwable $e) {
            $this->rescheduleOrFail($shipment, 'credential error: '.$e->getMessage());

            return;
        }

        $client = new SagawaClient($credential);
        $result = $client->checkFile($printId);
        $items  = $result['items'] ?? [];
        $first  = $items[0] ?? null;
        $url    = $first['url'] ?? null;

        if ($url) {
            $tracking = $shipment->tracking_number ?: $this->findTracking($result['parsed'] ?? []);
            if (! $tracking) {
                $tracking = $printId;
            }

            $shipment->markWaybillIssued(
                $tracking,
                $url,
                Carbon::now()->addDay(),
            );
            WaybillIssued::dispatch($shipment, $tracking, $url);

            return;
        }

        $this->rescheduleOrFail($shipment, 'pdf not ready');
    }

    private function rescheduleOrFail(Shipment $shipment, string $reason): void
    {
        $max = max(1, (int) config('sagawa.pdf_poll_max_attempts', 20));

        if ($shipment->pdf_poll_attempts >= $max) {
            $shipment->markWaybillFailed($reason);
            WaybillFailed::dispatch($shipment, null, $reason);

            return;
        }

        self::dispatch($shipment->id)
            ->delay(now()->addSeconds((int) config('sagawa.pdf_poll_interval', 30)));
    }

    /** @param  array<string,mixed>  $parsed */
    private function findTracking(array $parsed): ?string
    {
        $found = null;
        array_walk_recursive($parsed, function ($value, $k) use (&$found) {
            if (! $found && is_string($k) && strtolower($k) === 'toiawasenumber' && is_scalar($value)) {
                $found = (string) $value;
            }
        });

        return $found;
    }
}
