<?php

namespace Fujiba\SupplierFinder\Support;

use RuntimeException;

/** Thrown when a shop disallows us (robots.txt) or keeps rate-limiting. The shop is skipped and logged. */
class ShopBlocked extends RuntimeException
{
    public function __construct(public string $reason, string $message)
    {
        parent::__construct($message);
    }
}
