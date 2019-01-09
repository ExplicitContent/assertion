<?php

namespace ExplicitContent\Boost\Arrays;

use function ExplicitContent\Boost\BoostedString\dump;
use LogicException;

/**
 * Allows to access array keys with dot notation:
 *
 * Arr::set($array, 'foo.bar', 'value') is the same as $array['foo']['bar'] = 'value'
 * Arr::get($array, 'foo.bar') is the same as $array['foo']
 *
 * Dot char and backslash can be escaped:
 *
 * Arr::get($array, 'foo\.bar.baz') is the same as $array['foo.bar']['baz'].
 *
 * @internal
 */
final class ArrayDotNotationAccess
{
    private static $keys = [];

    public static function set(array $array, $key, $value)
    {
        $segments = self::extractSegments($key);

        $original = &$array;

        while (count($segments) > 1) {
            $segment = array_shift($segments);

            if (!isset($array[$segment])) {
                $array[$segment] = [];
            }

            if (!is_array($array[$segment])) {
                throw new LogicException(sprintf('Key "%s" (segment "%s") contains non-array: %s.', $key, $segment, dump($array)));
            }

            $array = &$array[$segment];
        }

        $array[array_shift($segments)] = $value;

        return $original;
    }

    public static function get(array $array, $key)
    {
        $keys = self::extractSegments($key);

        foreach ($keys as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                throw new LogicException(sprintf('Key "%s" (segment "%s") does not exist or contains non-array.', $key, $segment));
            }
        }

        return $array;
    }

    public static function exists(array $array, $key): bool
    {
        $keys = self::extractSegments($key);

        foreach ($keys as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return false;
            }
        }

        return true;
    }

    private static function extractSegments(string $key)
    {
        if (!isset(self::$keys[$key])) {
            if (count(self::$keys) > 10000) {
                self::$keys = [];
            }

            self::$keys[$key] = array_map(
                function (string $segment) {
                    return strtr($segment, ['\\.' => '.', '\\\\' => '\\']);
                },
                array_filter(
                    preg_split(
                        '/((?:[^\\\\.]|\\\\.)*)/', $key, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
                    ),
                    function (string $segment) {
                        return $segment !== '.';
                    }
                )
            );
        }

        return self::$keys[$key];
    }
}
