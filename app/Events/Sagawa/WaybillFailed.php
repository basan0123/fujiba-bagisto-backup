<?php

namespace App\Events\Sagawa;

use Fujiba\SagawaDelivery\Models\Shipment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WaybillFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Shipment $shipment,
        public readonly ?string $resultCode,
        public readonly ?string $reason = null,
    ) {}
}
