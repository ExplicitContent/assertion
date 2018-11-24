<?php

namespace ExplicitContent\Assertion;

use ExplicitContent\Assertion\Exceptions\AssertionContainsError;
use Throwable;

/**
 * @internal
 */
final class InternalAssertion
{
    public static function true(bool $value, string $message, Throwable $e = null): void
    {
        if ($value) {
            return;
        }

        throw new AssertionContainsError($message, $e);
    }
}
