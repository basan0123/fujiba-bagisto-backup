<?php

namespace Fujiba\OrdersAdmin\Exceptions;

use RuntimeException;

/**
 * Business-rule failure while editing an order (e.g. "order has no shipping
 * address", "already on that payment method", "insufficient stock").
 *
 * Both transports map this to a user-facing message:
 *   - admin REST  → HTTP 422 with {message, errors}
 *   - POS GraphQL → a {success:false, message} payload
 *
 * `errors` carries an optional list of field-level / multi-line problems
 * (used by the stock pre-flight, which can report several lines at once).
 */
class OrderEditException extends RuntimeException
{
    /** @param string[] $errors */
    public function __construct(string $message, public array $errors = [])
    {
        parent::__construct($message);
    }
}
