<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Services\Ocr\StubOcr;
use Fujiba\Accounting\Tests\TestCase;

class StubOcrTest extends TestCase
{
    public function test_returns_deterministic_empty_shape(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'stubocr');
        file_put_contents($tmp, 'fake');

        $a = (new StubOcr())->extract($tmp);
        $b = (new StubOcr())->extract($tmp);

        $this->assertSame($a, $b);

        $this->assertNull($a['vendor_name']);
        $this->assertNull($a['vendor_t_number']);
        $this->assertNull($a['receipt_date']);
        $this->assertNull($a['total_amount']);
        $this->assertNull($a['tax_amount']);
        $this->assertNull($a['tax_rate']);
        $this->assertNull($a['payment_method']);
        $this->assertSame(0.0, $a['confidence']);
        $this->assertIsArray($a['raw_json']);
        $this->assertSame('stub', $a['raw_json']['driver']);

        @unlink($tmp);
    }
}
