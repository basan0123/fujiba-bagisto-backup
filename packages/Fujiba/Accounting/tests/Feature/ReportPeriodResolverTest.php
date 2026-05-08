<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Services\Reports\ReportPeriodResolver;
use Fujiba\Accounting\Tests\TestCase;

class ReportPeriodResolverTest extends TestCase
{
    protected function r(): ReportPeriodResolver
    {
        return new ReportPeriodResolver();
    }

    public function test_year_month_resolves_to_calendar_month(): void
    {
        $p = $this->r()->resolve(['year_month' => '2026-04']);
        $this->assertSame('2026-04-01', $p['from']->toDateString());
        $this->assertSame('2026-04-30', $p['to']->toDateString());
    }

    public function test_invalid_year_month_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->r()->resolve(['year_month' => 'bogus']);
    }

    public function test_fiscal_year_lookup_uses_seeded_period(): void
    {
        // Sprint 1 seeded fiscal_year 2026 (2026-04-01 → 2027-03-31)
        $p = $this->r()->resolve(['fiscal_year' => 2026]);
        $this->assertSame('2026-04-01', $p['from']->toDateString());
        $this->assertSame('2027-03-31', $p['to']->toDateString());
    }

    public function test_unknown_fiscal_year_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->r()->resolve(['fiscal_year' => 1999]);
    }

    public function test_explicit_from_to(): void
    {
        $p = $this->r()->resolve(['from' => '2026-05-01', 'to' => '2026-05-15']);
        $this->assertSame('2026-05-01', $p['from']->toDateString());
        $this->assertSame('2026-05-15', $p['to']->toDateString());
    }

    public function test_to_before_from_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->r()->resolve(['from' => '2026-05-10', 'to' => '2026-05-01']);
    }

    public function test_as_of_resolves_to_epoch_to_date(): void
    {
        $p = $this->r()->resolve(['as_of' => '2026-05-08']);
        $this->assertSame('1970-01-01', $p['from']->toDateString());
        $this->assertSame('2026-05-08', $p['to']->toDateString());
    }
}
