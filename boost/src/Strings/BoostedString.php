<?php

namespace ExplicitContent\Boost\Strings;

use LogicException;
use ReflectionClass;

/**
 * Don't use it directly in you projects.
 * Backward compatibility is NOT guaranteed.
 *
 * @internal
 */
final class BoostedString
{
    /**
     * WARNING: this regex doesn't contain $ delimiter on purpose.
     */
    private const VALID_UTF8_CHAR_SEQUENCE_REGEX = '/^(?:
      [\x09\x0A\x0D\x20-\x7E]
    | [\xC2-\xDF][\x80-\xBF]
    | \xE0[\xA0-\xBF][\x80-\xBF]
    | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}
    | \xED[\x80-\x9F][\x80-\xBF]  
    | \xF0[\x90-\xBF][\x80-\xBF]{2}
    | [\xF1-\xF3][\x80-\xBF]{3} 
    | \xF4[\x80-\x8F][\x80-\xBF]{2}
    )*/xs';

    public static function isUtf8Encoded(string $string): bool
    {
        if (preg_match(self::VALID_UTF8_CHAR_SEQUENCE_REGEX, $string, $match) === 1) {
            return strlen($string) === strlen($match[0]);
        }

        return false;
    }

    public static function firstNonUtf8ByteSequence(string $string, int $bytes): ?string
    {
        if (preg_match(self::VALID_UTF8_CHAR_SEQUENCE_REGEX, $string, $match) === 1) {
            if (strlen($string) === strlen($match[0])) {
                return null;
            }

            return substr($string, strlen($match[0]), $bytes);
        }

        return substr($string, 0, $bytes);
    }

    public static function stringify($value): string
    {
        if (is_string($value)) {
            return $value;
        }

        return self::dump($value);
    }
    
    public static function dump($value, int $depth = 5): string
    {
        if (is_string($value)) {
            $val = $value;

            if (self::isUtf8Encoded($value)) {
                if (mb_strlen($value, 'utf-8') > 45) {
                    $val = mb_substr($value, 0, 42, 'utf-8') . '...';
                }
                return '"' . $val . '"';
            }

            return sprintf('%s%s', '\x' . wordwrap(bin2hex(substr($val, 0, 10)), 2, '\x', true), strlen($val) > 10 ? ' ...' : '');
        } elseif ($value === null) {

            return 'null';
        } elseif (is_bool($value)) {

            return $value ? 'true' : 'false';
        } elseif (is_scalar($value)) {

            return (string)$value;
        } elseif (is_array($value)) {
            if ($depth > 0) {
                return array2string($value, 10, false, $depth - 1);
            } else {
                return sprintf('(array) %d element(s)', count($value));
            }
        } elseif (is_object($value)) {
            $reflection = new ReflectionClass($value);

            if ($reflection->isAnonymous()) {
                $class = '[anonymous class';

                if ($reflection->getParentClass()) {
                    $class .= ' extends ' . $reflection->getName();
                }

                if ($interfaces = $reflection->getInterfaceNames()) {
                    $class .= ' implements ' . implode(', ', $interfaces);
                }

                $class .= ']';
            } else {
                $class = $reflection->getName();
            }

            if (method_exists($value, '__toString')) {
                return sprintf('%s (__toString → "%s")', $class, self::dump($value->__toString()));
            }

            if (method_exists($value, '__debugInfo')) {
                return sprintf('%s (__debugInfo → %s)', $class, self::dump($value->__debugInfo()));
            }

            return $class;
        } elseif (is_resource($value)) {

            return sprintf('resource "%s"', get_resource_type($value));
        }

        throw new LogicException(sprintf('Unknown type: "%s".', gettype($value)));
    }
}
