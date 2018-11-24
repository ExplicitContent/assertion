<?php

namespace ExplicitContent\Assertion\Exceptions;

use Error;
use Throwable;

final class AssertionContainsError extends Error
{
    public function __construct(string $message, Throwable $previous = null)
    {
        parent::__construct('[INTERNAL ERROR]: ' . $message, 0, $previous);
    }
}
