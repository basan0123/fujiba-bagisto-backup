<?php

namespace Fujiba\FacebookCatalog\Exceptions;

use RuntimeException;
use Throwable;

/**
 * A Graph API call that came back as an error.
 *
 * Exists so callers can tell a transient failure (rate limit, 5xx) from a
 * permanent one (bad token, missing permission, wrong object id) instead of
 * regex-scraping a flattened message string. Retrying a permanent error just
 * reproduces it on a timer, so PostPublisher keys its backoff off isRetryable().
 *
 * Extends RuntimeException to stay backward compatible: everything that used
 * to catch the RuntimeException GraphClient threw still catches this.
 */
class GraphApiException extends RuntimeException
{
    /**
     * Graph error codes that are worth retrying.
     *
     *   1, 2  — transient API/service unknown errors
     *   4     — app-level rate limit
     *   17    — user-level rate limit
     *   32    — page-level rate limit
     *   341   — application limit reached
     *   368   — temporarily blocked for policy violations
     *   613   — custom-level throttle
     */
    public const RETRYABLE_CODES = [1, 2, 4, 17, 32, 341, 368, 613];

    /**
     * Permanent auth/permission/target failures. Listed explicitly so a new
     * unknown code is not silently treated as fatal.
     *
     *   102        — session invalid
     *   190        — access token invalid/expired
     *   200 / 10   — permission denied (e.g. "not allowed to publish to other
     *                users' timelines" — publishing needs a PAGE token)
     *   100        — invalid parameter / unsupported object id
     *   803        — object does not exist
     */
    public const PERMANENT_CODES = [10, 100, 102, 190, 200, 803];

    public function __construct(
        string $message,
        protected int $httpStatus = 0,
        protected ?array $errorBody = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    /** The full decoded Graph payload, when the response carried JSON. */
    public function errorBody(): ?array
    {
        return $this->errorBody;
    }

    public function graphCode(): ?int
    {
        $code = $this->errorBody['error']['code'] ?? null;

        return is_numeric($code) ? (int) $code : null;
    }

    public function graphSubcode(): ?int
    {
        $sub = $this->errorBody['error']['error_subcode'] ?? null;

        return is_numeric($sub) ? (int) $sub : null;
    }

    public function graphType(): ?string
    {
        $type = $this->errorBody['error']['type'] ?? null;

        return is_string($type) ? $type : null;
    }

    /**
     * Should the caller schedule a backoff retry?
     *
     * Decided in this order:
     *   1. an explicitly retryable Graph code (rate limits / transient) -> yes
     *   2. an explicitly permanent Graph code                          -> no
     *   3. 5xx or 429, or no response at all (timeout)                 -> yes
     *   4. any other 4xx                                               -> no
     */
    public function isRetryable(): bool
    {
        $code = $this->graphCode();

        if ($code !== null && in_array($code, self::RETRYABLE_CODES, true)) {
            return true;
        }

        if ($code !== null && in_array($code, self::PERMANENT_CODES, true)) {
            return false;
        }

        if ($this->httpStatus === 429 || $this->httpStatus >= 500) {
            return true;
        }

        // No HTTP status means the request never completed (DNS, timeout).
        if ($this->httpStatus === 0) {
            return true;
        }

        return false;
    }

    /** Short operator-facing reason, used in the failure panel and logs. */
    public function classification(): string
    {
        return $this->isRetryable() ? 'transient' : 'permanent';
    }
}
