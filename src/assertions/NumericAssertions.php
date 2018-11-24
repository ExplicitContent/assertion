<?php

namespace ExplicitContent\Assertion;

use ExplicitContent\Assertion\Exceptions\AssertionFailed;

/**
 * @internal
 */
final class NumericAssertions
{
    private $number;

    public function __construct($number)
    {
        $this->number = $number;
    }

    public function zeroOrPositive(string $message = 'Value {subject} is negative.'): self
    {
        if ($this->number > 0) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->number]);
    }

    public function positive(string $message = 'Value {subject} is not positive.'): self
    {
        if ($this->number > 0) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->number]);
    }

    public function negative(string $message = 'Value {subject} is not negative.'): self
    {
        if ($this->number < 0) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->number]);
    }

    public function between($from, $to, string $message = 'Value {subject} is not between {from}..{to}.'): self
    {
        if ($this->number >= $from && $this->number <= $to) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->number, 'from' => $from, 'to' => $to]);
    }

    public function greaterThan($number, string $message = '{subject} is not greater than {number}.'): self
    {
        if ($this->number > $number) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->number, 'number' => $number]);
    }
    
    public function greaterOrEqThan($number, string $message = '{subject} is not greater or equal than {number}.'): self
    {
        if ($this->number >= $number) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->number, 'number' => $number]);
    }

    public function lessThan($number, string $message = '{subject} is not less than {number}.'): self
    {
        if ($this->number < $number) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->number, 'number' => $number]);
    }

    public function lessOrEqThan($number, string $message = '{subject} is not less or equal than {number}.'): self
    {
        if ($this->number <= $number) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->number, 'number' => $number]);
    }
}
