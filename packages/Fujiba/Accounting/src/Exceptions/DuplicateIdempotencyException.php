<?php

namespace Fujiba\Accounting\Exceptions;

use Fujiba\Accounting\Models\JournalEntry;
use RuntimeException;

class DuplicateIdempotencyException extends RuntimeException
{
    public function __construct(
        public readonly string $idempotencyKey,
        public readonly JournalEntry $existing,
    ) {
        parent::__construct("Duplicate idempotency_key '{$idempotencyKey}' — entry id={$existing->id} already exists.");
    }
}
