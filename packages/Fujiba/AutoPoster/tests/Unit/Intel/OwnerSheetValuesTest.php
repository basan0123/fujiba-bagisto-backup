<?php

namespace Fujiba\AutoPoster\Tests\Unit\Intel;

use Fujiba\AutoPoster\Services\Intel\OwnerSheetValues as V;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class OwnerSheetValuesTest extends TestCase
{
    public function test_counts_with_suffixes(): void
    {
        $this->assertSame(12300, V::count('12.3K'));
        $this->assertSame(12300, V::count('12.3k'));
        $this->assertSame(1200000, V::count('1.2M'));
        $this->assertSame(1250000, V::count('1.25M'));
        $this->assertSame(1234, V::count('1,234'));
        $this->assertSame(1234567, V::count('1,234,567'));
        $this->assertSame(12000, V::count('1.2万'));
        $this->assertSame(300000000, V::count('3億'));
        $this->assertSame(987, V::count(' 987 '));
        $this->assertSame(456, V::count('４５６'));            // full-width
        $this->assertSame(5100, V::count('5.1K views'));
        $this->assertSame(0, V::count('0'));
        $this->assertNull(V::count(''));
        $this->assertNull(V::count('-'));
        $this->assertNull(V::count(null));
    }

    public function test_bad_counts_throw(): void
    {
        foreach (['abc', '1.5', '12..3K', '-5', '1,23', 'K'] as $bad) {
            try {
                V::count($bad);
                $this->fail("accepted '{$bad}'");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_width_range_and_units(): void
    {
        $this->assertSame([112.0, null], V::width('112'));
        $this->assertSame([112.5, null], V::width('112.5cm'));
        $this->assertSame([110.0, null], V::width('１１０'));
        $this->assertSame([80.0, null], V::width('80'));
        $this->assertSame([150.0, null], V::width('150'));
        $this->assertSame([null, null], V::width(''));
        $this->assertNull(V::width('79.9')[0]);
        $this->assertStringContainsString('out of range', V::width('151')[1]);
        $this->assertStringContainsString('metres', V::width('1.12')[1]);
        $this->assertStringContainsString('metres', V::width('1.1m')[1]);
        $this->assertStringContainsString('not a number', V::width('wide')[1]);
    }

    public function test_length_range_and_units(): void
    {
        $this->assertSame([2.0, null], V::length('2'));
        $this->assertSame([1.5, null], V::length('1,5'));
        $this->assertSame([3.0, null], V::length('3 m'));
        $this->assertSame([0.5, null], V::length('0.5'));
        $this->assertSame([5.0, null], V::length('5'));
        $this->assertSame([null, null], V::length(' '));
        $this->assertStringContainsString('cm', V::length('200')[1]);
        $this->assertStringContainsString('cm', V::length('200cm')[1]);
        $this->assertStringContainsString('out of range', V::length('0.4')[1]);
        $this->assertStringContainsString('out of range', V::length('5.5')[1]);
    }
}
