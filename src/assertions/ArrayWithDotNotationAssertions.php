<?php

namespace ExplicitContent\Assertion;

use ExplicitContent\Assertion\Exceptions\AssertionFailed;
use ExplicitContent\Boost\Arrays\ArrayDotNotationAccess;
use function ExplicitContent\Boost\Strings\array2list;

/**
 * @internal
 */
final class ArrayWithDotNotationAssertions
{
    private $subject;

    public function __construct(array $subject)
    {
        $this->subject = $subject;
    }

    public function keyExists(string $key, string $message = 'Array {subject} doesn\'t contain "{key}" (using dot notation).'): self
    {
        if (ArrayDotNotationAccess::exists($this->subject, $key)) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->subject, 'key' => $key]);
    }

    public function keysExist(array $keys, string $message = 'Array {subject} doesn\'t contain the following key(s): {missing} (using dot notation).'): self
    {
        $missing = [];

        foreach ($keys as $key) {
            if (!ArrayDotNotationAccess::exists($this->subject, $key)) {
                $missing[] = $key;
            }
        }

        if (empty($missing)) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->subject, 'missing' => array2list($missing)]);
    }

    public function keyDoesNotExist(string $key, string $message = 'Array {subject} DOES contain key "{key}", but must not.'): self
    {
        if (!ArrayDotNotationAccess::exists($this->subject, $key)) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->subject, 'key' => $key]);
    }
}
