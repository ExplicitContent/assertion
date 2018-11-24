<?php

namespace ExplicitContent\Assertion\Fixtures\Contracts;

use Closure;
use ExplicitContent\Assertion\Assertion;

final class JsonFormatter
{
    private $formatter;

    public function __construct(Closure $formatter)
    {
        Assertion::callable($formatter)->respectsMethodSignature(self::class, 'format');

        $this->formatter = $formatter;
    }

    public function format(string $json): string
    {
        return call_user_func($this->formatter, $json);
    }
}
