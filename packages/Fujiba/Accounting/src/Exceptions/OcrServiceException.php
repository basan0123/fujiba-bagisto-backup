<?php

namespace Fujiba\Accounting\Exceptions;

use RuntimeException;

class OcrServiceException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $requestId = null,
        public readonly ?int $httpStatus = null,
    ) {
        parent::__construct($message);
    }
}
