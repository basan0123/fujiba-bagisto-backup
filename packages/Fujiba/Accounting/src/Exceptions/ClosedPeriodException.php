<?php

namespace Fujiba\Accounting\Exceptions;

use RuntimeException;

class ClosedPeriodException extends RuntimeException
{
    public function __construct(public readonly string $entryDate)
    {
        parent::__construct("No open fiscal period covers entry date {$entryDate}");
    }
}
