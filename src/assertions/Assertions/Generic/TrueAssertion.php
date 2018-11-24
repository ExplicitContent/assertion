<?php

namespace ExplicitContent\Assertion\Assertions\Generic;

use ExplicitContent\Assertion\Exceptions\AssertionFailed;

/**
 * @internal
 */
final class TrueAssertion
{
    public static function assert(bool $value, string $message = ''): void
    {
        if ($value === true) {
            return;
        }

        throw new AssertionFailed($message, []);
    }
}
