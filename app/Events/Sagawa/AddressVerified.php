<?php

namespace App\Events\Sagawa;

use Fujiba\SagawaDelivery\Models\Shipment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when Sagawa's checkaddress API returns a successful match (S0-xxxx)
 * or an admin has manually approved a candidate correction.
 */
class AddressVerified
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Shipment $shipment,
        public readonly ?int $verifiedBy = null,
    ) {}
}
