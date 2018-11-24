<?php

namespace ExplicitContent\Assertion\Assertions\Generic\Assertions;

use ExplicitContent\Assertion\Exceptions\AssertionFailed;

/**
 * @internal
 */
final class NotNullAssertion
{
    public static function assert($value, string $message = ''): void
    {
        if ($value !== null) {
            return;
        }

        throw new AssertionFailed($message, []);
    }
}
