<?php

namespace ExplicitContent\Assertion;

use Closure;
use ExplicitContent\Assertion\Exceptions\AssertionFailed;
use ExplicitContent\Boost\BoostedString\BoostedString;
use function ExplicitContent\Boost\BoostedString\fstr;
use ReflectionClass;
use function ExplicitContent\Boost\BoostedString\dump;

final class Assertion
{
    public static function true(bool $value, string $message = ''): void
    {
        if ($value === true) {
            return;
        }

        throw new AssertionFailed($message, []);
    }

    public static function false(bool $value, string $message = ''): void
    {
        if ($value === false) {
            return;
        }

        throw new AssertionFailed($message, []);
    }

    public static function null($value, string $message = ''): void
    {
        if ($value === null) {
            return;
        }

        throw new AssertionFailed($message, []);
    }

    public static function notNull($value, string $message = ''): void
    {
        if ($value !== null) {
            return;
        }

        throw new AssertionFailed($message, []);
    }

    public static function same($value, $expected, string $message = '{expected} is not the same as {value}.{notice}'): void
    {
        if ($value === $expected) {
            return;
        }

        $notice = '';

        if ($value == $expected) {
            $notice = ' However, non-strict comparision says they are equal.';
        }

        throw new AssertionFailed($message, ['expected' => dump($expected), 'value' => dump($value), 'notice' => $notice]);
    }

    public static function array($array): ArrayAssertions
    {
        self::true(is_array($array), fstr('Value {value} is not an array.', ['value' => $array]));

        return new ArrayAssertions($array);
    }

    public static function callable($callable): CallableAssertions
    {
        self::true(is_callable($callable), fstr('Value {value} is not a callable.', ['value' => $callable]));

        return new CallableAssertions(Closure::fromCallable($callable));
    }

    public static function class($class): ClassAssertions
    {
        self::true(
            (is_string($class) && class_exists($class))
            || $class instanceof ReflectionClass,
            fstr('{value} is nor an existing class name and neither \ReflectionClass.', ['value' => $class])
        );

        return new ClassAssertions(is_string($class) ? new ReflectionClass($class) : $class);
    }

    public static function float($value): FloatAssertions
    {
        self::true(is_float($value), fstr('Value {value} is not a float.', ['value' => $value]));

        return new FloatAssertions($value);
    }

    public static function numeric($value): NumericAssertions
    {
        self::true(
            is_numeric($value),
            fstr(
                'Value {value} is not numeric (int, float or string-that-looks-like-number).',
                ['value' => dump($value)]
            )
        );

        return new NumericAssertions($value);
    }

    public static function object($object): ObjectAssertions
    {
        self::true(is_object($object), fstr('Value {value} is not an object.', ['value' => $object]));

        return new ObjectAssertions($object);
    }

    /**
     * WARNING: it doesn't check for non-printable characters because technically binary is not obligated to contain them.
     */
    public static function stringBinary($string): BinaryStringAssertions
    {
        self::true(is_string($string), fstr('Value {value} is not a string.', ['value' => $string]));

        return new BinaryStringAssertions($string);
    }

    public static function string8bit($string): StringAssertions
    {
        self::true(is_string($string), fstr('Value {value} is not a string.', ['value' => $string]));

        return new StringAssertions($string, '8bit');
    }

    public static function string($string): StringAssertions
    {
        self::true(is_string($string), fstr('Value {value} is not a string.', ['value' => $string]));

        $invalidSequence = BoostedString::firstNonUtf8ByteSequence($string, 10);

        if ($invalidSequence === null) {
            return new StringAssertions($string, 'utf-8');
        }

        throw new AssertionFailed(
            'The string is not properly UTF-8 encoded, first invalid sequence [offset {offset} byte(s)]: 0x{hex}; use ->binary() to work with non-UTF-8 strings.', [
                'subject' => $string,
                'offset' => strpos($string, $invalidSequence),
                'hex' => bin2hex($invalidSequence)
            ]
        );
    }

    public static function unreachable(string $message = 'Unreachable code is actually reachable.'): void
    {
        throw new AssertionFailed($message, []);
    }
}
