<?php

namespace Fujiba\AutoPoster\Ops\Insights;

class GraphException extends \RuntimeException
{
    public function __construct(string $message, public readonly ?int $graphCode = null, public readonly int $httpStatus = 0)
    {
        parent::__construct($message);
    }

    /** OAuth / permission errors: stop the whole run, retrying won't help. */
    public function isAuthError(): bool
    {
        return in_array($this->graphCode, [102, 190, 10, 200], true) || $this->httpStatus === 401;
    }

    public function isRateLimit(): bool
    {
        return in_array($this->graphCode, [4, 17, 32, 613], true) || $this->httpStatus === 429;
    }
}
