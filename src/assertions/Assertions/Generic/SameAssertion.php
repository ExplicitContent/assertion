<?php

namespace ExplicitContent\Assertion\Assertions\Generic\Assertions;

use ExplicitContent\Assertion\Exceptions\AssertionFailed;
use function ExplicitContent\Boost\BoostedString\dump;

/**
 * @internal
 */
final class SameAssertion
{
    public static function assert($value, $expected, string $message = '{expected} is not the same as {value}.{notice}'): void
    {
        if ($value === $expected) {
            return;
        }

        $notice = '';

        if ($value == $expected) {
            $notice = ' However, non-strict comparision says they are equal.';
        }

        throw new AssertionFailed($message, ['expected' => dump($expected), 'value' => dump($value), 'notice' => $notice]);
    }
}
