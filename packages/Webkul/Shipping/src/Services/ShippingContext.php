<?php

namespace Webkul\Shipping\Services;

class ShippingContext
{
    public float  $baseShipping       = 0;
    public float  $coolFee            = 0;
    public float  $frozenFee          = 0;
    public bool   $freeShippingApplied = false;
    public string $freeShippingReason  = '';
    public array  $bonusItems          = [];
    public array  $appliedRules        = [];
    public array  $breakdown           = [];
}
