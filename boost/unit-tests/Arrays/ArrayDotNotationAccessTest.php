<?php

namespace ExplicitContent\Boost\Arrays;

use PHPStan\Testing\TestCase;

final class ArrayDotNotationAccessTest extends TestCase
{
    public function testAccess()
    {
        $array = [
            'foo' => 42,
            'bar' => ['baz' => 666],
        ];

        $this->assertSame(42, ArrayDotNotationAccess::get($array, 'foo'));
        $this->assertSame(666, ArrayDotNotationAccess::get($array, 'bar.baz'));

        $array = ArrayDotNotationAccess::set($array, 'bar.qux', 777);

        $this->assertSame(['baz' => 666, 'qux' => 777], $array['bar']);

        $this->assertTrue(ArrayDotNotationAccess::exists($array, 'foo'));
        $this->assertFalse(ArrayDotNotationAccess::exists($array, 'foo.bar'));
        $this->assertTrue(ArrayDotNotationAccess::exists($array, 'bar.baz'));
        $this->assertTrue(ArrayDotNotationAccess::exists($array, 'bar.qux'));
    }
}
