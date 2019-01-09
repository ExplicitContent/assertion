<?php

namespace ExplicitContent\Assertion;

use ExplicitContent\Assertion\Exceptions\AssertionFailed;
use function ExplicitContent\Boost\BoostedString\dump;

/**
 * @internal
 */
final class BinaryStringAssertions
{
    private $string;

    public function __construct(string $string)
    {
        $this->string = $string;
    }

    public function lengthExactly(int $expected, string $message = 'Expected {expected} bytes, the actual string contains {actual}.'): self
    {
        if (strlen($this->string) === $expected) {
            return $this;
        }

        throw new AssertionFailed($message, ['actual' => strlen($this->string), 'expected' => $expected]);
    }

    public function in(array $list, string $message = 'Value {subject} is not in {list}.'): self
    {
        if (in_array($this->string, $list, true)) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => dump($this->string), 'list' => $list]);
    }

    public function notIn(array $list, string $message = 'Value {subject} in the list: {list}.'): self
    {
        if (!in_array($this->string, $list, true)) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => dump($this->string), 'list' => $list]);
    }
}
