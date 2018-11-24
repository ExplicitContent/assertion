<?php

namespace ExplicitContent\Assertion\Exceptions;

use Error;
use ExplicitContent\Assertion\Assertion;
use ExplicitContent\Boost\StackTrace\StackTrace;
use function ExplicitContent\Boost\Strings\fstr;

final class AssertionFailed extends Error
{
    public function __construct(string $message, array $args)
    {
        if ($message === '') {
            try {
                $line = StackTrace::fromThrowable($this)->findTopLineWithClass(Assertion::class);
                $args['assertion'] = $line->parseAssertionCode();
                $args['file'] = $line->getFile();
                $args['line'] = $line->getLine();

                parent::__construct(fstr('{assertion} failed in {file} at line {line}.', $args));
            } catch (\Throwable $e) {
                parent::__construct('Assertion failed.', 0, $e);
            }
        } else {
            parent::__construct(fstr($message, $args));
        }
    }
}
