<?php

namespace ExplicitContent\Assertion;

use ExplicitContent\Assertion\Exceptions\AssertionFailed;
use ReflectionParameter;

final class ParameterAssertions
{
    private $parameter;

    /**
     * @internal
     */
    public function __construct(ReflectionParameter $parameter)
    {
        $this->parameter = $parameter;
    }

    public function hasType(string $type, string $message = 'Parameter "{param.name}" must type hint against "{type}".'): self
    {
        $actualType = $this->parameter->getType();

        if ($actualType && $actualType->getName() === $type) {
            return $this;
        }

        throw new AssertionFailed($message, [
            'param.name' => $this->parameter->getName(),
            'type' => $type,
        ]);
    }

    public function hasTypeWhichIsSubClassOf(string $type, string $message = 'Parameter "{param.name}" must have type that is sub class of "{type}".')
    {
        $actualType = $this->parameter->getType();

        if ($actualType && is_subclass_of($actualType->getName(), $type)) {
            return $this;
        }

        throw new AssertionFailed($message, [
            'param.name' => $this->parameter->getName(),
            'type' => $type,
        ]);
    }

    public function nullable(string $message = 'Parameter "{param.name}" must allow null.'): self
    {
        if ($this->parameter->allowsNull()) {
            return $this;
        }

        throw new AssertionFailed($message, [
            'param.name' => $this->parameter->getName(),
        ]);
    }

    public function notNullable(string $message = 'Parameter "{param.name}" must not be nullable.'): self
    {
        if (!$this->parameter->allowsNull()) {
            return $this;
        }

        throw new AssertionFailed($message, [
            'param.name' => $this->parameter->getName(),
        ]);
    }

    public function required(string $message = 'Parameter "{param.name}" must be required.'): self
    {
        if (!$this->parameter->isOptional()) {
            return $this;
        }

        throw new AssertionFailed($message, [
            'param.name' => $this->parameter->getName(),
        ]);
    }

    public function optional(string $message = 'Parameter "{param.name}" must be optional.'): self
    {
        if (!$this->parameter->isOptional()) {
            return $this;
        }

        throw new AssertionFailed($message, [
            'param.name' => $this->parameter->getName(),
        ]);
    }

    public function variadic(string $message = 'Parameter "{param.name}" is not variadic.'): self
    {
        if ($this->parameter->isVariadic()) {
            return $this;
        }

        throw new AssertionFailed($message, [
            'param.name' => $this->parameter->getName(),
        ]);
    }
}
