<?php

namespace ExplicitContent\Assertion;

use ExplicitContent\Assertion\Exceptions\AssertionFailed;
use ReflectionClass;

/**
 * @internal
 */
final class ObjectAssertions
{
    private $object;
    private $reflection;

    public function __construct(object $object)
    {
        $this->object = $object;
        $this->reflection = new ReflectionClass($object);
    }

    public function class(): ClassAssertions
    {
        return new ClassAssertions($this->reflection);
    }

    public function instanceof(string $class, string $message = 'The object of {subjectClass} is not instance of {class}.'): self
    {
        if ($this->object instanceof $class) {
            return $this;
        }

        throw new AssertionFailed($message, ['subjectClass' => get_class($this->object), 'class' => $class]);
    }
}
