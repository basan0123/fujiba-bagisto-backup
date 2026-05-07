<?php

namespace Fujiba\Accounting\Exceptions;

use RuntimeException;

class UnknownAccountException extends RuntimeException
{
    public function __construct(public readonly string $accountCode)
    {
        parent::__construct("Unknown account code: {$accountCode}");
    }
}
