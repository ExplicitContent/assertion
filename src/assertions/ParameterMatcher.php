<?php

namespace ExplicitContent\Assertion;

use Closure;
use ReflectionParameter;

final class ParameterMatcher
{
    private $parameters;
    private $namedMap;

    /**
     * @param ReflectionParameter[] $parameters
     * @param Closure $namedMap
     */
    public function __construct(array $parameters, Closure $namedMap)
    {
        $this->parameters = $parameters;
        $this->namedMap = $namedMap;
    }

    /**
     * @return ParameterAssertions
     */
    public function first(): ParameterAssertions
    {
        Assertion::true(isset($this->parameters[0]), 'Callable does not have parameters.');

        return $this->idx(0);
    }

    /**
     * @param string $name
     * @return ParameterAssertions
     */
    public function named(string $name): ParameterAssertions
    {
        $namedMap = $this->namedMap;

        return new ParameterAssertions($namedMap($name));
    }

    /**
     * @param int $index
     * @return ParameterAssertions
     */
    public function idx(int $index): ParameterAssertions
    {
        Assertion::array($this->parameters)->keyExists($index, 'Callable does not contain parameter with index {key}.');

        return new ParameterAssertions($this->parameters[$index]);
    }
}
