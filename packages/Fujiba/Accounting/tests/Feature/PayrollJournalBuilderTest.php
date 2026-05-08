<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Models\PayrollSlip;
use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Services\JournalPoster;
use Fujiba\Accounting\Services\Payroll\PayrollJournalBuilder;
use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PayrollJournalBuilderTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeSlip(array $overrides = []): PayrollSlip
    {
        $staff = Staff::create([
            'staff_code' => 'PJB-' . uniqid(),
            'qr_token'   => 'pjb-' . uniqid(),
            'name_ja'    => '仕訳テスト',
            'employment_type' => 'part_time',
            'is_active'  => true,
        ]);
        return PayrollSlip::create(array_merge([
            'staff_id'   => $staff->id,
            'year_month' => '2026-04',
            'pay_date'   => '2026-04-25',
            'channel_id' => 1,
            'base_pay'        => 200000,
            'gross_pay'       => 200000,
            'health_insurance'=> 9980,
            'pension'         => 18300,
            'employment_insurance' => 1200,
            'income_tax'      => 4290,
            'resident_tax'    => 6000,
            'other_deduction' => 0,
            'total_deduction' => 39770,
            'net_pay'         => 160230,
            'status'          => 'draft',
        ], $overrides));
    }

    public function test_balanced_payload_with_all_lines(): void
    {
        $slip = $this->makeSlip();
        $payload = (new PayrollJournalBuilder())->build($slip);

        $totalDr = 0; $totalCr = 0;
        foreach ($payload['lines'] as $l) {
            $totalDr += (int) ($l['debit'] ?? 0);
            $totalCr += (int) ($l['credit'] ?? 0);
        }
        $this->assertSame($totalDr, $totalCr, 'Payroll journal payload must balance');
        $this->assertSame((int) $slip->gross_pay, $totalDr);
    }

    public function test_zero_resident_tax_skips_line(): void
    {
        $slip = $this->makeSlip(['resident_tax' => 0, 'total_deduction' => 33770, 'net_pay' => 166230]);
        $payload = (new PayrollJournalBuilder())->build($slip);
        $codes = array_map(fn ($l) => $l['account_code'], $payload['lines']);
        $this->assertNotContains('2230', $codes, 'No 預り金(住民税) line when resident_tax = 0');
    }

    public function test_full_pipeline_produces_real_entry(): void
    {
        $slip = $this->makeSlip();

        $payload = (new PayrollJournalBuilder())->build($slip);
        $entry = app(JournalPoster::class)->post($payload);

        $this->assertNotNull($entry->id);
        $this->assertSame('payroll', $entry->source_type);
        $this->assertSame((int) $slip->id, (int) $entry->source_id);
        $this->assertSame((int) $slip->gross_pay, (int) $entry->lines->sum('debit'));
        $this->assertSame((int) $slip->gross_pay, (int) $entry->lines->sum('credit'));
    }

    public function test_zero_gross_throws(): void
    {
        $slip = $this->makeSlip(['gross_pay' => 0, 'base_pay' => 0, 'net_pay' => 0]);
        $this->expectException(\InvalidArgumentException::class);
        (new PayrollJournalBuilder())->build($slip);
    }
}
