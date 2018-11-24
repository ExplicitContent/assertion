<?php

namespace ExplicitContent\Boost\ErrorHandling;

final class SandboxInvocationResult
{
    private $returnValue;
    private $errors;

    /**
     * @param mixed $returnValue
     * @param string[] $errors
     */
    public function __construct($returnValue, array $errors)
    {
        $this->returnValue = $returnValue;
        $this->errors = $errors;
    }

    public function getReturnValue()
    {
        return $this->returnValue;
    }

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getLastError(): ?string
    {
        return $this->errors[0] ?? null;
    }
}
