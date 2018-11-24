<?php

namespace ExplicitContent\Assertion\Assertions\Generic;

use ExplicitContent\Assertion\Exceptions\AssertionFailed;

final class UnreachableAssertion
{
    public static function assert(string $message = 'Unreachable code is actually reachable.'): void
    {
        throw new AssertionFailed($message, []);
    }
}
