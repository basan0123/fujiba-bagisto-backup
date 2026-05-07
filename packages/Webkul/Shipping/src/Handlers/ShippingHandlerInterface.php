<?php

namespace Webkul\Shipping\Handlers;

use Webkul\Shipping\DTOs\CartDTO;
use Webkul\Shipping\Services\ShippingContext;

interface ShippingHandlerInterface
{
    public function handle(CartDTO $cart, ShippingContext $ctx): void;
    public function getPriority(): int;
    public function supports(CartDTO $cart): bool;
}
