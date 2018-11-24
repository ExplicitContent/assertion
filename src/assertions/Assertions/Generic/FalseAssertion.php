<?php

namespace ExplicitContent\Assertion\Assertions\Generic;

use ExplicitContent\Assertion\Exceptions\AssertionFailed;

/**
 * @internal
 */
final class FalseAssertion
{
    public static function assert(bool $value, string $message = ''): void
    {
        if ($value === false) {
            return;
        }

        throw new AssertionFailed($message, []);
    }
}

