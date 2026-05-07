<?php

namespace App\Events\Sagawa;

use Fujiba\SagawaDelivery\Models\Shipment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a waybill has been issued AND the PDF URL has been retrieved.
 */
class WaybillIssued
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Shipment $shipment,
        public readonly string $trackingNumber,
        public readonly ?string $pdfUrl = null,
    ) {}
}
