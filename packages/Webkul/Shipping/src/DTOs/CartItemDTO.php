<?php

namespace Webkul\Shipping\DTOs;

readonly class CartItemDTO
{
    public function __construct(
        public int    $productId,
        public int    $categoryId,
        public string $temperatureType,
        public int    $quantity,
        public float  $price,
        public float  $rowTotal,
        public float  $weight,
        public bool   $excludeFromFreeShipping,
        public bool   $excludeFromCoolFee,
    ) {}
}
