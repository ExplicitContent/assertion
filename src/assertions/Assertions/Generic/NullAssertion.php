<?php

namespace ExplicitContent\Assertion\Assertions\Generic\Assertions;

use ExplicitContent\Assertion\Exceptions\AssertionFailed;

/**
 * @internal
 */
final class NullAssertion
{
    public static function assert($value, string $message = ''): void
    {
        if ($value === true) {
            return;
        }

        throw new AssertionFailed($message, []);
    }
}
