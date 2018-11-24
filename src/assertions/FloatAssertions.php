<?php

namespace ExplicitContent\Assertion;

use ExplicitContent\Assertion\Exceptions\AssertionFailed;

/**
 * @internal
 */
final class FloatAssertions
{
    private $subject;

    public function __construct(float $subject)
    {
        $this->subject = $subject;
    }

    public function equalsTo(float $another, float $epsilon, $message = '{subject} is not equal to {number} (epsilon = {epsilon}.'): self
    {
        if (abs($this->subject - $another) < $epsilon) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->subject, 'number' => $another, 'epsilon' => $epsilon]);
    }
}
