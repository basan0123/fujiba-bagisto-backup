<?php

namespace Fujiba\Accounting\Exceptions;

use RuntimeException;

class UnbalancedEntryException extends RuntimeException
{
    public function __construct(
        public readonly int $debit,
        public readonly int $credit,
    ) {
        parent::__construct("Journal entry is unbalanced: debit ¥{$debit} ≠ credit ¥{$credit}");
    }
}
