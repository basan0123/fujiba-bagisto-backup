<?php

namespace Webkul\Shipping\DTOs;

readonly class ShippingResultDTO
{
    public function __construct(
        public float  $baseShipping,
        public float  $coolFee,
        public float  $frozenFee,
        public float  $total,
        public bool   $freeShippingApplied,
        public string $freeShippingReason,
        public array  $bonusItems,
        public array  $appliedRules,
        public array  $breakdown,
    ) {}
}
