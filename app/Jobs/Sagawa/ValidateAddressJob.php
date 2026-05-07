<?php

namespace App\Jobs\Sagawa;

use App\Events\Sagawa\AddressCheckFailed;
use App\Events\Sagawa\AddressVerified;
use Fujiba\SagawaDelivery\Models\Shipment;
use Fujiba\SagawaDelivery\Services\CredentialResolver;
use Fujiba\SagawaDelivery\Services\SagawaClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Calls Sagawa checkaddress for a single shipment and updates its status
 * based on the returned result_code family.
 */
class ValidateAddressJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public readonly int $shipmentId) {}

    public function handle(CredentialResolver $resolver): void
    {
        $shipment = Shipment::find($this->shipmentId);
        if (! $shipment) {
            return;
        }

        // Only act on shipments still awaiting verification.
        if ($shipment->status !== Shipment::STATUS_PENDING_VERIFICATION) {
            return;
        }

        $address  = $shipment->original_address ?? [];
        $postcode = (string) ($address['postcode'] ?? $address['zip'] ?? '');
        $joined   = trim(implode(' ', array_filter([
            $address['prefecture'] ?? null,
            $address['city']       ?? null,
            $address['address']    ?? $address['address1'] ?? null,
            $address['address2']   ?? null,
        ])));

        if ($postcode === '' || $joined === '') {
            $shipment->update([
                'address_check_at'     => now(),
                'address_check_result' => ['error' => 'missing postcode or address'],
            ]);
            AddressCheckFailed::dispatch($shipment, null, 'missing postcode or address');

            return;
        }

        try {
            $credential = $resolver->for(
                tenantId: function_exists('tenant') ? tenant('id') : null,
                channelId: $shipment->channel_id,
            );
        } catch (\Throwable $e) {
            AddressCheckFailed::dispatch($shipment, null, 'no credentials: '.$e->getMessage());

            return;
        }

        $client = new SagawaClient($credential);
        $result = $client->checkAddress($postcode, $joined);

        $shipment->update([
            'address_check_at'     => now(),
            'address_check_result' => $result['parsed'] ?? [],
            'sagawa_raw_request'   => null,
            'sagawa_raw_response'  => $result['response'] ?? null,
        ]);

        $code = (string) ($result['result_code'] ?? '');

        if (str_starts_with($code, 'S')) {
            $shipment->update(['status' => Shipment::STATUS_ADDRESS_VERIFIED]);
            AddressVerified::dispatch($shipment, null);

            return;
        }

        AddressCheckFailed::dispatch($shipment, $code === '' ? null : $code, $code ?: 'no result code');
    }
}
