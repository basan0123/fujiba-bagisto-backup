<?php

namespace App\Events\Sagawa;

use Fujiba\SagawaDelivery\Models\Shipment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when checkaddress returns a non-success code or the job could not
 * reach the API after retries. The shipment remains in pending_verification
 * so an admin can reconcile manually.
 */
class AddressCheckFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Shipment $shipment,
        public readonly ?string $resultCode,
        public readonly ?string $message = null,
    ) {}
}
