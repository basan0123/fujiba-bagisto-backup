<?php

namespace Webkul\Shipping\DTOs;

readonly class CartDTO
{
    public function __construct(
        public int    $cartId,
        public int    $channelId,
        public string $prefectureCode,
        public float  $subtotal,
        public float  $subtotalExcludingExclusions,
        public float  $totalWeight,
        public array  $items,
        public string $currencyCode,
    ) {}
}
