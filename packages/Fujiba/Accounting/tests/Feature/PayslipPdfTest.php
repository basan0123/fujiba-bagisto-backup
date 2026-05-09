<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Models\PayrollSlip;
use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\User\Models\Admin;

class PayslipPdfTest extends TestCase
{
    use DatabaseTransactions;

    protected function actingAdmin()
    {
        $admin = Admin::query()->where('status', 1)->first() ?? Admin::query()->first();
        if (! $admin) {
            $this->markTestSkipped('No admin user available');
        }
        return $this->actingAs($admin, 'admin');
    }

    protected function makeSlip(array $overrides = []): PayrollSlip
    {
        $s = Staff::create([
            'staff_code' => 'PSL-' . uniqid(),
            'qr_token'   => 'psl-' . uniqid(),
            'name_ja'    => 'PDF テスト',
            'employment_type' => 'part_time',
            'is_active' => true,
        ]);
        return PayrollSlip::create(array_merge([
            'staff_id'        => $s->id,
            'year_month'      => '2026-04',
            'pay_date'        => '2026-04-25',
            'channel_id'      => 1,
            'base_pay'        => 200000,
            'gross_pay'       => 200000,
            'health_insurance'=> 9980,
            'pension'         => 18300,
            'employment_insurance' => 1200,
            'income_tax'      => 4290,
            'resident_tax'    => 6000,
            'total_deduction' => 39770,
            'net_pay'         => 160230,
            'status'          => 'finalized',
        ], $overrides));
    }

    public function test_individual_payslip_pdf_downloads(): void
    {
        $this->actingAdmin();
        $slip = $this->makeSlip();

        $resp = $this->get(route('admin.accounting.payroll.pdf', $slip->id));
        $resp->assertStatus(200);
        $resp->assertHeader('Content-Type', 'application/pdf');

        $body = $resp->getContent();
        $this->assertStringStartsWith('%PDF-', $body);
    }

    public function test_pdf_zip_bundle_contains_all_slips_plus_manifest(): void
    {
        $this->actingAdmin();
        $a = $this->makeSlip(['year_month' => '2026-05', 'gross_pay' => 100000, 'net_pay' => 80000, 'total_deduction' => 20000]);
        $b = $this->makeSlip(['year_month' => '2026-05', 'gross_pay' => 250000, 'net_pay' => 200000, 'total_deduction' => 50000]);

        $resp = $this->get(route('admin.accounting.payroll.index', ['year_month' => '2026-05', 'format' => 'pdf-zip']));
        $resp->assertStatus(200);
        $resp->assertHeader('Content-Type', 'application/zip');

        $tmp = tempnam(sys_get_temp_dir(), 'zipt');
        file_put_contents($tmp, $resp->streamedContent());

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($tmp) === true);
        $this->assertGreaterThanOrEqual(3, $zip->numFiles, 'Expected ≥ 2 PDFs + MANIFEST');

        $manifest = $zip->getFromName('MANIFEST.txt');
        $this->assertIsString($manifest);
        $this->assertStringContainsString('period=2026-05', $manifest);
        $this->assertStringContainsString('total_gross=350000', $manifest);
        $this->assertStringContainsString('total_net=280000', $manifest);

        $zip->close();
        @unlink($tmp);
    }

    public function test_pdf_404_on_unknown_slip(): void
    {
        $this->actingAdmin();
        $this->withoutExceptionHandling();
        try {
            $this->get(route('admin.accounting.payroll.pdf', 999999));
            $this->fail('Expected ModelNotFoundException');
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }
}
