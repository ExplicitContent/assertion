<?php

namespace ExplicitContent\Assertion;

use ExplicitContent\Assertion\Exceptions\AssertionFailed;
use ReflectionClass;

/**
 * @internal
 */
final class ClassAssertions
{
    private $class;
    private $reflection;

    public function __construct(ReflectionClass $class)
    {
        $this->class = $class->name;
        $this->reflection = $class;
    }

    public function subclassOf(string $class, string $message = 'Class {subject} is not instance of "{class}.'): self
    {
        if ($this->reflection->isSubclassOf($class)) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->class, 'class' => $class]);
    }

    public function implements(string $interface, string $message = 'Class {subject} does not implement {interface}.'): self
    {
        if ($this->reflection->implementsInterface($interface)) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->class, 'interface' => $interface]);
    }

    public function userDefined(string $message = 'Class {subject} is internal one.'): self
    {
        if ($this->reflection->isUserDefined()) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->class]);
    }

    public function final(string $message = 'Class {subject} is open for inheritance.')
    {
        if ($this->reflection->isFinal()) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->class]);
    }

    public function intendedToBeFinal(string $message = 'Class {subject} is open for inheritance and has no @final tags.{warning}')
    {
        if (
            $this->reflection->isFinal()
            || ($this->reflection->getDocComment() && preg_match('/^\s*\*\s*@final/m', (string)$this->reflection->getDocComment()) > 0)
        ) {
            return $this;
        }

        throw new AssertionFailed($message, [
            'subject' => $this->class,
            'warning' => $this->reflection->getDocComment() === false ? ' WARNING: phpdoc in the class was not found, accelerator could cut it out.' : '',
        ]);
    }

    public function openForInheritance(string $message = '')
    {
        if (
            $this->reflection->isFinal()
            || (!$this->reflection->getDocComment() || preg_match('/^\s*\*\s*@final/m', (string)$this->reflection->getDocComment()) === 0)
        ) {
            return $this;
        }

        if ($message === '') {
            if ($this->reflection->isFinal()) {
                $message = 'Class {subject} is final or contains @final phpdoc tag.';
            } else {
                $message = 'Class {subject} contains @final phpdoc tag.';
            }
        }

        throw new AssertionFailed($message, ['subject' => $this->class]);
    }
}
