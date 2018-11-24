<?php

namespace ExplicitContent\Assertion;

use ExplicitContent\Assertion\Exceptions\AssertionFailed;
use ReflectionType;

final class ReturnTypeAssertions
{
    private $reflection;

    /**
     * @internal
     */
    public function __construct(?ReflectionType $reflectionType)
    {
        $this->reflection = $reflectionType;
    }

    public function is(string $type, string $message = 'Return value of the callable must have type hinting "{type}".'): self
    {
        if ($this->reflection && $this->reflection->getName() === $type) {
            return $this;
        }

        throw new AssertionFailed($message, ['type' => $type]);
    }

    public function nullable(string $message = 'Return value of the callable must be nullable.'): self
    {
        if (!$this->reflection || $this->reflection->allowsNull()) {
            return $this;
        }

        throw new AssertionFailed($message, []);
    }

    public function notNullable(string $message = 'Return value of the callable must not be nullable.'): self
    {
        if (!$this->reflection && !$this->reflection->allowsNull()) {
            return $this;
        }

        throw new AssertionFailed($message, []);
    }

    public function void(string $message = 'Return value of the callable must be void.'): self
    {
        if ($this->reflection && $this->reflection->getName() === 'void') {
            return $this;
        }

        throw new AssertionFailed($message, []);
    }

    public function voidOrNotSpecified(string $message = 'Return value of the callable must be void or just not specified.'): self
    {
        if (!$this->reflection || $this->reflection->getName() === 'void') {
            return $this;
        }

        throw new AssertionFailed($message, []);
    }
}
