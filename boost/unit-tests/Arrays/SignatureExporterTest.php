<?php

namespace ExplicitContent\Boost\Arrays;

use Closure;
use DateTimeImmutable;
use ExplicitContent\Boost\Behaviors\OffersUniqueHash;
use ExplicitContent\Boost\Callables\SignatureExporter;
use PHPStan\Testing\TestCase;

final class SignatureExporterTest extends TestCase
{
    public function setUp()
    {
        // $this->generate();
    }

    /**
     * @dataProvider provideArgumentsForClassMethod
     */
    public function testExportingFromClassMethod(string $class, string $method, string $expectedSignature)
    {
        $this->assertSame(file_get_contents($expectedSignature), SignatureExporter::exportFromClassMethod($class, $method));
    }

    public function provideArgumentsForClassMethod()
    {
        return [
            'user-land-class' => [
                SignatureExporter::class,
                'exportFromClassMethod',
                __DIR__ . '/Signatures/user-land-class.txt'
            ],
            'user-land-interface' => [
                OffersUniqueHash::class,
                'toHash',
                __DIR__ . '/Signatures/user-land-interface.txt'
            ],
            'internal-class' => [
                DateTimeImmutable::class,
                'format',
                __DIR__ . '/Signatures/internal-class.txt'
            ]
        ];
    }

    /**
     * @dataProvider provideArgumentsForClosure
     */
    public function testExportingFromClosure(Closure $closure, string $expectedSignature)
    {
        $this->assertSame(file_get_contents($expectedSignature), SignatureExporter::exportFromClosure($closure));
    }

    public function provideArgumentsForClosure()
    {
        $a = 42;

        return [
            'closure' => [
                function () use ($a): int  {
                    return $a;
                },
                __DIR__ . '/Signatures/closure.txt'
            ],
            'static-closure' => [
                static function (string $bar): int  {
                    return crc32($bar);
                },
                __DIR__ . '/Signatures/static-closure.txt'
            ],
            'variadic' => [
                static function (string ...$bar): string  {
                    return implode(', ', $bar);
                },
                __DIR__ . '/Signatures/variadic.txt'
            ],
        ];
    }

    public function generate()
    {
        foreach ($this->provideArgumentsForClassMethod() as $args) {
            file_put_contents($args[2], SignatureExporter::exportFromClassMethod($args[0], $args[1]));
        }

        foreach ($this->provideArgumentsForClosure() as $args) {
            file_put_contents($args[1], SignatureExporter::exportFromClosure($args[0]));
        }
    }
}
