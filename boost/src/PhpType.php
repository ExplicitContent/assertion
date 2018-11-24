<?php

namespace ExplicitContent\Boost;

use ReflectionClass;
use ReflectionException;

/**
 * @internal
 */
final class PhpType
{
    private const BOOL = 'boolean';
    private const INT = 'integer';
    private const FLOAT = 'double';
    private const STRING = 'string';
    private const ARRAY = 'array';
    private const OBJECT = 'object';
    private const RESOURCE = 'resource';
    private const CLOSED_RESOURCE = 'resource (closed)';

    private const MAP = [
        self::BOOL => self::BOOL,
        self::INT => self::INT,
        self::FLOAT => self::FLOAT,
        self::STRING => self::STRING,
        self::ARRAY => self::ARRAY,
        self::OBJECT => self::OBJECT,
        self::RESOURCE => self::RESOURCE,
        self::CLOSED_RESOURCE => 'resource',
        'bool' => self::BOOL,
        'int' => self::INT,
        'float' => self::FLOAT,

        // pseudo
        'callable' => 'callable',
    ];

    public static function normalize(string $type): ?string
    {
        if (!isset(self::MAP[$type])) {
            try {
                $class = new ReflectionClass($type);
                return $class->getName();
            } catch (ReflectionException $e) {
                return null;
            }
        }

        return self::MAP[$type];
    }
}
