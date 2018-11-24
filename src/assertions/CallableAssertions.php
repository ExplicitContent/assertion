<?php

namespace ExplicitContent\Assertion;

use Closure;
use ExplicitContent\Assertion\Exceptions\AssertionFailed;
use ExplicitContent\Boost\Callables\SignatureExporter;
use ReflectionFunction;
use ReflectionParameter;

final class CallableAssertions
{
    private $subject;
    private $reflection;
    private $parameters;

    private $named;
    private $parameterMatcher;
    private $signature;

    public function __construct(Closure $callable)
    {
        $this->subject = $callable;
        $this->reflection = new ReflectionFunction($callable);
        $this->parameters = $this->reflection->getParameters();
    }

    public function respectsMethodSignature(string $class, string $method, string $message = 'The callable doesn\'t respect signature of {class}::{method}.'): self
    {
        if ($this->hasSameFingerPrint(SignatureExporter::exportFromClassMethod($class, $method))) {
            return $this;
        }

        throw new AssertionFailed($message, ['class' => $class, 'method' => $method]);
    }

    public function respectsSignatureOfSample(Closure $sample, string $message = 'The callable doesn\'t respect signature of the same closure.'): self
    {
        if ($this->hasSameFingerPrint(SignatureExporter::exportFromClosure($sample))) {
            return $this;
        }

        throw new AssertionFailed($message, []);
    }

    public function satisfies(Closure $assertions): self
    {
        $assertions($this->getParameterMatcher(), $this->returnType());

        return $this;
    }

    public function parametersSatisfy(Closure $assertions): self
    {
        $assertions($this->getParameterMatcher());

        return $this;
    }

    public function returnTypeSatisfies(Closure $assertions): self
    {
        $assertions($this->returnType());

        return $this;
    }

    /**
     * Alternative to parametersSatisfy(), but in this case method chaining breaks.
     *
     * @return ParameterMatcher
     */
    public function parameters(): ParameterMatcher
    {
        return $this->getParameterMatcher();
    }

    /**
     * Alternative to returnTypeSatisfies(), but method chaining also becomes broken.
     *
     * @return ReturnTypeAssertions
     */
    public function returnType(): ReturnTypeAssertions
    {
        return new ReturnTypeAssertions($this->reflection->getReturnType());
    }

    private function hasSameFingerPrint(string $exported)
    {
        if ($this->signature === null) {
            $this->signature = SignatureExporter::exportFromClosure($this->subject);
        }

        return $exported === $this->signature;
    }

    private function getParameterMatcher(): ParameterMatcher
    {
        if ($this->parameterMatcher === null) {
            $this->parameterMatcher = new ParameterMatcher(
                $this->parameters,
                function (string $name) {
                    if ($this->named === null) {
                        $this->named = array_combine(
                            array_map(
                                function (ReflectionParameter $parameter) {
                                    return $parameter->getName();
                                },
                                $this->parameters
                            ),
                            $this->parameters
                        );
                    }

                    Assertion::array($this->named)->keyExists($name, 'Callable does not have parameter named {key}.');

                    return $this->named[$name];
                }
            );
        }

        return $this->parameterMatcher;
    }
}
