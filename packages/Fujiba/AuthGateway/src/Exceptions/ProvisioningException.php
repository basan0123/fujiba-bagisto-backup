<?php

namespace Fujiba\AuthGateway\Exceptions;

class ProvisioningException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $step,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
