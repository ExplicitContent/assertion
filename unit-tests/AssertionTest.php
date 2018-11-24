<?php

namespace ExplicitContent\Assertion;

use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use ExplicitContent\Assertion\Exceptions\AssertionContainsError;
use ExplicitContent\Assertion\Exceptions\AssertionFailed;
use ExplicitContent\Assertion\Fixtures\FinalOpenClasses\ConditionallyOpenClass;
use ExplicitContent\Assertion\Fixtures\FinalOpenClasses\FinalClass;
use ExplicitContent\Assertion\Fixtures\FinalOpenClasses\OpenClass;
use ExplicitContent\Boost\Behaviors\OffersUniqueHash;
use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;

final class AssertionTest extends TestCase
{
    private const EXPECTED_MESSAGE_REGEX = 'expected-message-regex';
    private const EXPECTED_MESSAGE = 'expected-message';

    public function testCorrectAssertions(): void
    {
        Assertion::true(42 === 42);
        Assertion::false(42 !== 42);
        Assertion::null(null);
        Assertion::notNull(42);

        Assertion::array(['foo' => 'bar', 'foo42' => 'baz'])
            ->of('string')
            ->contains('baz')
            ->unique()
            ->notEmpty()
            ->keysExist(['foo', 'foo42'])
            ->keyExists('foo')
            ->keyDoesNotExist('hey');

        Assertion::object(new DateTimeImmutable('2000-01-01', new DateTimeZone('UTC')))
            ->instanceof(DateTimeInterface::class);

        Assertion::callable(function (stdClass $foo, string $bar): stdClass { $foo->bar = $bar; return $foo; })
            ->parametersSatisfy(function (ParameterMatcher $matcher) {
                $matcher->first()->hasType(stdClass::class);
                $matcher->idx(1)->hasType('string');
            })
            ->returnType()->is(stdClass::class);

        Assertion::array([42, 43])
            ->contains(42)
            ->indexed()
            ->keysExist([0, 1]);

        Assertion::string('2000-01-01')->matchesRegEx('/^(?<year>\d{4})-(?<month>\d{2})-(?<day>\d{2})$/');

        Assertion::numeric(42)->positive()->lessThan(43);
        Assertion::numeric(42.5)->positive()->greaterThan(42)->between(42.4, 43.1);

        $this->assertTrue(true); // Suppress "This test did not perform any assertions"
    }

    /**
     * @dataProvider provideFailingAssertions
     */
    public function testFailedAssertions(Closure $assertion, string $expectedMessage, bool $regex): void
    {
        $this->expectException(AssertionFailed::class);

        if ($regex) {
            $this->expectExceptionMessageRegExp($expectedMessage);
        } else {
            $this->expectExceptionMessage($expectedMessage);
        }

        $assertion();
    }

    /**
     * This is quite fragile test. Probably, makes sense to refactor it and/or add mutation testing.
     *
     * @dataProvider provideValidAssertions
     */
    public function testValidAssertions(Closure $assertion): void
    {
        $assertion();

        $this->assertTrue(true); // Suppress "This test did not perform any assertions"
    }

    /**
     * @dataProvider provideErrors
     */
    public function testErrors(Closure $assertion, string $expectedMessage, bool $regex): void
    {
        $this->expectException(AssertionContainsError::class);

        if ($regex) {
            $this->expectExceptionMessageRegExp($expectedMessage);
        } else {
            $this->expectExceptionMessage($expectedMessage);
        }

        $assertion();
    }

    public function provideFailingAssertions(): array
    {
        return array_map(
            function (array $row) {
                if (!(isset($row['invalid'][self::EXPECTED_MESSAGE_REGEX]) xor isset($row['invalid'][self::EXPECTED_MESSAGE]))) {
                    throw new LogicException('Specify either "%s" or "%s", not both.', self::EXPECTED_MESSAGE, self::EXPECTED_MESSAGE_REGEX);
                }

                return [
                    $row['invalid']['assertion'],
                    isset($row['invalid'][self::EXPECTED_MESSAGE_REGEX]) ? $row['invalid'][self::EXPECTED_MESSAGE_REGEX] : $row['invalid'][self::EXPECTED_MESSAGE],
                    isset($row['invalid'][self::EXPECTED_MESSAGE_REGEX])
                ];
            },
            $this->provideData()
        );
    }

    public function provideValidAssertions(): array
    {
        return array_map(
            function (array $row) {
                return [$row['valid']];
            },
            $this->provideData()
        );
    }

    public function provideData()
    {
        return [
            'true' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::true(42 === 43);
                    },
                    self::EXPECTED_MESSAGE_REGEX => '/Assertion::true\(42 === 43\)/'
                ],

                'valid' => function () {
                    Assertion::true(42 === 42);
                },
            ],

            'false' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::false(42 === 42, 'It seems like 42 === 42.');
                    },
                    self::EXPECTED_MESSAGE => 'It seems like 42 === 42.',
                ],

                'valid' => function () {
                    Assertion::false(42 === 43);
                },
            ],

            'null' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::null(new stdClass());
                    },
                    self::EXPECTED_MESSAGE_REGEX => '/Assertion::null\(new \\\\stdClass\(\)\) failed .+/',
                ],

                'valid' => function () {
                    Assertion::null(null);
                },
            ],
            
            'not-null' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::notNull(null);
                    },
                    self::EXPECTED_MESSAGE_REGEX => '/Assertion::notNull\(null\) failed .+/',
                ],

                'valid' => function () {
                    Assertion::notNull(new stdClass());
                },
            ],

            'same' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::same(42, "42");
                    },
                    self::EXPECTED_MESSAGE => '"42" is not the same as 42. However, non-strict comparision says they are equal.',
                ],

                'valid' => function () {
                    Assertion::same(42, 42);
                },
            ],

            // array

            'array-of' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::array(['foo', 'bar'])->of('callable');
                    },
                    self::EXPECTED_MESSAGE => 'Expected array of callable, found "foo".',
                ],

                'valid' => function () {
                    Assertion::array(['foo', 'bar'])->of('string');
                },
            ],

            'array-values-satisfy' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::array(['foo', 'bar'])->valuesSatisfy(function (string $value) {
                            return strlen($value) >= 4;
                        });
                    },
                    self::EXPECTED_MESSAGE => 'Element "foo" does not satisfy the condition.',
                ],

                'valid' => function () {
                    Assertion::array(['foo', 'bar'])->valuesSatisfy(function (string $value) {
                        return strlen($value) === 3;
                    });
                },
            ],

            'array-keys-and-values-satisfy' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::array(['foo' => 'bar', 'bar' => 'foo'])->keysAndValuesSatisfy(function (string $key, string $value) {
                            return strlen($key) === 3 && strlen($value) >= 4;
                        });
                    },
                    self::EXPECTED_MESSAGE => 'Element with key "foo" and value "bar" does not satisfy the condition.',
                ],

                'valid' => function () {
                    Assertion::array(['foo' => 'bar', 'bar' => 'foo'])->keysAndValuesSatisfy(function (string $key, string $value) {
                        return strlen($key) === 3 && strlen($value) === 3;
                    });
                },
            ],

            'array-key-exists' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::array(['foo' => 42])->keyExists('bar');
                    },
                    self::EXPECTED_MESSAGE => 'Key "bar" doesn\'t exist.',
                ],

                'valid' => function () {
                    Assertion::array(['foo' => 42])->keyExists('foo');
                },
            ],

            'array-keys-exist' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::array(['foo' => 42, 'bar' => 666])->keysExist(['foo', 'baz']);
                    },
                    self::EXPECTED_MESSAGE => 'Array {"foo": 42, "bar": 666} doesn\'t contain the following key(s): "baz".',
                ],

                'valid' => function () {
                    Assertion::array(['foo' => 42, 'bar' => 666])->keysExist(['foo', 'bar']);
                },
            ],

            'array-key-does-not-exist' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::array(['foo' => 42])->keyDoesNotExist('foo');
                    },
                    self::EXPECTED_MESSAGE => 'Key "foo" exists, but must not.',
                ],

                'valid' => function () {
                    Assertion::array(['foo' => 42])->keyDoesNotExist('bar');
                },
            ],

            'array-not-empty' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::array([])->notEmpty();
                    },
                    self::EXPECTED_MESSAGE => 'Array is empty.',
                ],

                'valid' => function () {
                    Assertion::array(['foo'])->notEmpty();
                },
            ],

            'array-indexed' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::array(['foo' => 'bar'])->indexed();
                    },
                    self::EXPECTED_MESSAGE => 'Array {"foo": "bar"} is not indexed (contains string keys or order of numeric keys is broken).',
                ],

                'valid' => function () {
                    Assertion::array(['foo', 'bar'])->indexed();
                },
            ],

            'array-contains' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::array(['foo' => 'bar'])->contains('foo');
                    },
                    self::EXPECTED_MESSAGE => 'Array {"foo": "bar"} does not contain value "foo".',
                ],

                'valid' => function () {
                    Assertion::array(['foo' => 'bar'])->contains('bar');
                },
            ],

            'array-contains-unique-scalars' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::array(['foo', 'foo', 'bar'])->unique();
                    },
                    self::EXPECTED_MESSAGE => 'Array contains non-unique values, at least this one: "foo".',
                ],

                'valid' => function () {
                    Assertion::array(['foo', 'bar'])->unique();
                },
            ],

            'array-contains-unique-objects' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::array([
                            new class implements OffersUniqueHash {
                                public function toHash(): string
                                {
                                    return md5('foo');
                                }
                            },
                            new class implements OffersUniqueHash {
                                public function toHash(): string
                                {
                                    return md5('foo');
                                }
                            }
                        ])
                        ->unique();
                    },
                    self::EXPECTED_MESSAGE => 'Array contains non-unique values, at least this one: [anonymous class implements ExplicitContent\Boost\Behaviors\OffersUniqueHash]:acbd18db4cc2f85cedef654fccc4a4d8.',
                ],

                'valid' => function () {
                    Assertion::array([
                        new class implements OffersUniqueHash {
                            public function toHash(): string
                            {
                                return md5('foo');
                            }
                        },
                        new class implements OffersUniqueHash {
                            public function toHash(): string
                            {
                                return md5('bar');
                            }
                        }
                    ])
                    ->unique();
                },
            ],

            'array-dot-notated-key-exists' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::array(['foo' => ['bar' => ['baz' => 42]]])->dotNotated()->keyExists('foo.bar.xyz');
                    },
                    self::EXPECTED_MESSAGE => 'Array {"foo": {"bar": {"baz": 42}}} doesn\'t contain "foo.bar.xyz" (using dot notation).',
                ],

                'valid' => function () {
                    Assertion::array(['foo' => ['bar' => ['baz' => 42]]])->dotNotated()->keyExists('foo.bar.baz');
                },
            ],

            'array-dot-notated-keys-exist' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::array(['foo' => ['bar' => ['baz' => 42]]])->dotNotated()->keysExist(['foo', 'foo.baz', 'foo.bar', 'foo.bar.xyz']);
                    },
                    self::EXPECTED_MESSAGE => 'Array {"foo": {"bar": {"baz": 42}}} doesn\'t contain the following key(s): "foo.baz", "foo.bar.xyz" (using dot notation).',
                ],

                'valid' => function () {
                    Assertion::array(['foo' => ['bar' => ['baz' => 42]]])->dotNotated()->keysExist(['foo', 'foo.bar', 'foo.bar.baz']);
                },
            ],

            'array-dot-notated-key-does-not-exist' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::array(['foo' => ['bar' => ['baz' => 42]]])->dotNotated()->keyDoesNotExist('foo.bar');
                    },
                    self::EXPECTED_MESSAGE => 'Array {"foo": {"bar": {"baz": 42}}} DOES contain key "foo.bar", but must not.',
                ],

                'valid' => function () {
                    Assertion::array(['foo' => ['bar' => ['baz' => 42]]])->dotNotated()->keyDoesNotExist('foo.baz');
                },
            ],

            'binary-length' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::stringBinary("\x00\x01\x02\x03")->lengthExactly(3);
                    },
                    self::EXPECTED_MESSAGE => 'Expected 3 bytes, the actual string contains 4.',
                ],

                'valid' => function () {
                    Assertion::stringBinary("\x00\x01\x02\x03")->lengthExactly(4);
                },
            ],

            'binary-in' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::stringBinary("\x00")->in(["\x01", "\x02"]);
                    },
                    self::EXPECTED_MESSAGE => 'Value \x00 is not in [\x01, \x02].',
                ],

                'valid' => function () {
                    Assertion::stringBinary("\x01")->in(["\x01", "\x02"]);
                },
            ],

            'binary-not-in' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::stringBinary("\x01")->notIn(["\x01", "\x02"]);
                    },
                    self::EXPECTED_MESSAGE => 'Value \x01 in the list: [\x01, \x02].',
                ],

                'valid' => function () {
                    Assertion::stringBinary("\x00")->notIn(["\x01", "\x02"]);
                },
            ],

            'callable-parameters-satisfy' => [
                'invalid' => [
                    'assertion' => function () {
                        $callable = function (DateTimeImmutable $date): DateTimeImmutable {
                            return $date->modify('+1 day');
                        };

                        Assertion::callable($callable)->parametersSatisfy(function (ParameterMatcher $matcher) {
                            $matcher->first()->hasType(DateTimeImmutable::class)->nullable();
                        });
                    },
                    self::EXPECTED_MESSAGE => 'Parameter "date" must allow null.',
                ],

                'valid' => function () {
                    $callable = function (?DateTimeImmutable $date): DateTimeImmutable {
                        return $date ? $date->modify('+1 day') : new DateTimeImmutable('2000-01-01', new DateTimeZone('UTC'));
                    };

                    Assertion::callable($callable)->parametersSatisfy(function (ParameterMatcher $matcher) {
                        $matcher->first()->hasType(DateTimeImmutable::class)->nullable();
                    });
                },
            ],

            'callable-return-type-satisfies' => [
                'invalid' => [
                    'assertion' => function () {
                        $callable = function (stdClass $date): DateTimeImmutable {
                            return new DateTimeImmutable('2000-01-01', new DateTimeZone('UTC'));
                        };

                        Assertion::callable($callable)->returnTypeSatisfies(function (ReturnTypeAssertions $assertions) {
                            $assertions->is(stdClass::class)->nullable();
                        });
                    },
                    self::EXPECTED_MESSAGE => 'Return value of the callable must have type hinting "stdClass".',
                ],

                'valid' => function () {
                    $callable = function (stdClass $date): ?stdClass {
                        return $date;
                    };

                    Assertion::callable($callable)->returnTypeSatisfies(function (ReturnTypeAssertions $assertions) {
                        $assertions->is(stdClass::class)->nullable();
                    });
                },
            ],

            'class-is-user-defined' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::class(DateTimeImmutable::class)->userDefined();
                    },
                    self::EXPECTED_MESSAGE => 'Class DateTimeImmutable is internal one.',
                ],

                'valid' => function () {
                    Assertion::class(FinalClass::class)->final();
                },
            ],

            'class-open-for-inheritance' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::class(ConditionallyOpenClass::class)->openForInheritance();
                    },
                    self::EXPECTED_MESSAGE => 'Class ExplicitContent\Assertion\Fixtures\FinalOpenClasses\ConditionallyOpenClass contains @final phpdoc tag.',
                ],

                'valid' => function () {
                    Assertion::class(OpenClass::class)->openForInheritance();
                },
            ],

            'class-is-final' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::class(OpenClass::class)->final();
                    },
                    self::EXPECTED_MESSAGE => 'Class ExplicitContent\Assertion\Fixtures\FinalOpenClasses\OpenClass is open for inheritance.',
                ],

                'valid' => function () {
                    Assertion::class(FinalClass::class)->final();
                },
            ],

            'class-is-intended-to-be-final' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::class(OpenClass::class)->intendedToBeFinal();
                    },
                    self::EXPECTED_MESSAGE => 'Class ExplicitContent\Assertion\Fixtures\FinalOpenClasses\OpenClass is open for inheritance and has no @final tags. WARNING: phpdoc in the class was not found, accelerator could cut it out.',
                ],

                'valid' => function () {
                    Assertion::class(FinalClass::class)->intendedToBeFinal();
                    Assertion::class(ConditionallyOpenClass::class)->intendedToBeFinal();
                },
            ],

            'class-respects-interface' => [
                'invalid' => [
                    'assertion' => function () {
                        Assertion::class(OpenClass::class)->intendedToBeFinal();
                    },
                    self::EXPECTED_MESSAGE => 'Class ExplicitContent\Assertion\Fixtures\FinalOpenClasses\OpenClass is open for inheritance and has no @final tags. WARNING: phpdoc in the class was not found, accelerator could cut it out.',
                ],

                'valid' => function () {
                    Assertion::class(FinalClass::class)->intendedToBeFinal();
                    Assertion::class(ConditionallyOpenClass::class)->intendedToBeFinal();
                },
            ],
        ];
    }

    public function provideErrors()
    {
        return array_map(
            function (array $row) {
                if (!(isset($row[self::EXPECTED_MESSAGE_REGEX]) xor isset($row[self::EXPECTED_MESSAGE]))) {
                    throw new LogicException('Specify either "expected-regex" or "expected-message", not both.');
                }

                return [
                    $row['assertion'],
                    isset($row[self::EXPECTED_MESSAGE_REGEX]) ? $row[self::EXPECTED_MESSAGE_REGEX] : $row[self::EXPECTED_MESSAGE],
                    isset($row[self::EXPECTED_MESSAGE_REGEX])
                ];
            },
            [
                'invalid_pattern' => [
                    'assertion' => function () {
                        Assertion::string('foo')->matchesRegEx('invalidPattern##');
                    },
                    self::EXPECTED_MESSAGE => '[INTERNAL ERROR]: RegEx "invalidPattern##" failed with error "preg_match(): Delimiter must not be alphanumeric or backslash" and code "1".',
                ],
            ]
        );
    }
}
