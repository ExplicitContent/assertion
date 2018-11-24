<?php

namespace ExplicitContent\Assertion;

use Closure;
use ExplicitContent\Assertion\Exceptions\AssertionContainsError;
use ExplicitContent\Assertion\Exceptions\AssertionFailed;
use ExplicitContent\Boost\Behaviors\OffersUniqueHash;
use ExplicitContent\Boost\PhpType;
use ReflectionClass;
use function ExplicitContent\Boost\Strings\array2list;
use function ExplicitContent\Boost\Strings\dump;

/**
 * @internal
 */
final class ArrayAssertions
{
    private $subject;

    public function __construct(array $subject)
    {
        $this->subject = $subject;
    }

    public function dotNotated(): ArrayWithDotNotationAssertions
    {
        return new ArrayWithDotNotationAssertions($this->subject);
    }

    public function of(string $type, string $message = 'Expected array of {type}, found {value}.'): self
    {
        InternalAssertion::true(strtolower($type) !== 'null', '->of(null) is not allowed.');

        $value = null;

        $classOrPrimitive = PhpType::normalize($type);

        if ($classOrPrimitive === null) {
            throw new AssertionContainsError(
                sprintf('object(...)->of(...) expects PHP type (include pseudo "callable") or class name, but %s provided.', dump($type))
            );
        }

        if (class_exists($classOrPrimitive)) {
            $reflection = new ReflectionClass($classOrPrimitive);

            foreach ($this->subject as $item) {
                if (!is_object($item) || !$reflection->isInstance($item)) {
                    $value = $item;
                    break;
                }
            }
        } elseif ($type === 'callable') {
            foreach ($this->subject as $item) {
                if (!is_callable($item)) {
                    $value = $item;
                    break;
                }
            }
        }  else {
            foreach ($this->subject as $item) {
                if (gettype($item) !== $classOrPrimitive) {
                    $value = $item;
                    break;
                }
            }
        }

        if ($value === null) {
            return $this;
        }

        throw new AssertionFailed($message, ['type' => $type, 'value' => dump($value)]);
    }

    public function valuesSatisfy(Closure $condition, string $message = 'Element {value} does not satisfy the condition.'): self
    {
        foreach ($this->subject as $key => $value) {
            if (!$condition($value)) {
                throw new AssertionFailed($message, ['value' => dump($value)]);
            }
        }

        return $this;
    }

    public function keysAndValuesSatisfy(Closure $condition, string $message = 'Element with key {key} and value {value} does not satisfy the condition.'): self
    {
        foreach ($this->subject as $key => $value) {
            if (!$condition($key, $value)) {
                throw new AssertionFailed($message, ['key' => dump($key), 'value' => dump($value)]);
            }
        }

        return $this;
    }

    public function keyExists($key, string $message = 'Key {key} doesn\'t exist.'): self
    {
        if (array_key_exists($key, $this->subject)) {
            return $this;
        }

        throw new AssertionFailed($message, ['key' => dump($key)]);
    }

    public function keysExist(array $keys, string $message = 'Array {subject} doesn\'t contain the following key(s): {missing}.'): self
    {
        $missing = [];

        foreach ($keys as $key) {
            if (!array_key_exists($key, $this->subject)) {
                $missing[] = $key;
            }
        }

        if (empty($missing)) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->subject, 'missing' => array2list($missing)]);
    }

    public function keyDoesNotExist($key, string $message = 'Key {key} exists, but must not.'): self
    {
        if (!array_key_exists($key, $this->subject)) {
            return $this;
        }

        throw new AssertionFailed($message, ['key' => dump($key)]);
    }

    public function notEmpty(string $message = 'Array is empty.'): self
    {
        if (!empty($this->subject)) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->subject]);
    }

    public function indexed(string $message = 'Array {subject} is not indexed (contains string keys or order of numeric keys is broken).'): self
    {
        if (array_values($this->subject) === $this->subject) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->subject]);
    }

    public function contains($value, string $message = 'Array {subject} does not contain value {needle}.'): self
    {
        if (in_array($value, $this->subject, true)) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->subject, 'needle' => dump($value)]);
    }

    public function unique(string $message = 'Array contains non-unique values, at least this one: {duplicate}.'): self
    {
        $processed = [];

        foreach ($this->subject as $key => $value) {
            if (is_scalar($value)) {
                $key = gettype($value) . ':' . $value;
            } elseif (is_array($value)) {
                $key = 'a:' . json_encode($value);
            } elseif (is_object($value)) {
                if ($value instanceof OffersUniqueHash) {
                    $key = 'o:' . $value->toHash();
                } else {
                    $key = 'o:' . spl_object_id($value);
                }
            } else {
                throw new AssertionContainsError(sprintf('Array contains value which cannot be checked for uniqueness: %s.', dump($value)));
            }

            if (isset($processed[$key])) {
                $duplicate = $value instanceof OffersUniqueHash ? sprintf('%s:%s', dump($value), $value->toHash()) : dump($value);

                throw new AssertionFailed($message, [
                    'subject' => $this->subject,
                    'duplicate' => $duplicate,
                    'keys' => [$processed[$key], $key],
                ]);
            }

            $processed[$key] = $key;
        }

        return $this;
    }
}
