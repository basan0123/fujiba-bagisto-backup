<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Models\Setting;
use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Services\Payroll\ResidentTaxImporter;
use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ResidentTaxImporterTest extends TestCase
{
    use DatabaseTransactions;

    protected function tmpCsv(string $content, string $encoding = 'UTF-8'): string
    {
        $path = tempnam(sys_get_temp_dir(), 'rti_') . '.csv';
        if ($encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, $encoding, 'UTF-8');
        }
        file_put_contents($path, $content);
        return $path;
    }

    protected function makeStaff(string $code): Staff
    {
        return Staff::create([
            'staff_code' => $code,
            'qr_token'   => 'rti-' . uniqid(),
            'name_ja'    => 'RTI ' . $code,
            'employment_type' => 'full_time',
            'is_active'  => true,
        ]);
    }

    public function test_saitama_csv_imports_to_settings(): void
    {
        $s = $this->makeStaff('S001');
        $csv = "従業員番号,6月分,7月分,8月分,9月分,10月分,11月分,12月分,1月分,2月分,3月分,4月分,5月分\n"
             . "S001,12000,12000,12000,12000,12000,12000,12000,11500,11500,11500,11500,11500\n";

        $svc = new ResidentTaxImporter();
        $r = $svc->import($this->tmpCsv($csv, 'SJIS-win'), 'saitama_city', 2026);

        $this->assertSame(1, $r['imported']);
        $this->assertSame(0, $r['skipped']);
        $this->assertSame(12, $r['keys_written']);

        $this->assertSame(12000, $svc->get($s->id, '2026-06'));
        $this->assertSame(12000, $svc->get($s->id, '2026-12'));
        $this->assertSame(11500, $svc->get($s->id, '2027-01'));
        $this->assertSame(11500, $svc->get($s->id, '2027-05'));
    }

    public function test_adachi_ku_csv_uses_english_month_columns(): void
    {
        $s = $this->makeStaff('A042');
        $csv = "社員コード,JUN,JUL,AUG,SEP,OCT,NOV,DEC,JAN,FEB,MAR,APR,MAY\n"
             . "A042,8500,8500,8500,8500,8500,8500,8500,8000,8000,8000,8000,8000\n";

        $svc = new ResidentTaxImporter();
        $r = $svc->import($this->tmpCsv($csv, 'SJIS-win'), 'adachi_ku', 2026);

        $this->assertSame(1, $r['imported']);
        $this->assertSame(8500, $svc->get($s->id, '2026-06'));
        $this->assertSame(8000, $svc->get($s->id, '2027-03'));
    }

    public function test_dry_run_writes_nothing(): void
    {
        $s = $this->makeStaff('DR001');
        $csv = "従業員番号,6月分,7月分,8月分,9月分,10月分,11月分,12月分,1月分,2月分,3月分,4月分,5月分\n"
             . "DR001,5000,5000,5000,5000,5000,5000,5000,5000,5000,5000,5000,5000\n";

        $before = Setting::count();
        (new ResidentTaxImporter())->import($this->tmpCsv($csv, 'SJIS-win'), 'saitama_city', 2026, dryRun: true);
        $this->assertSame($before, Setting::count());
    }

    public function test_unknown_staff_code_is_listed_as_not_found(): void
    {
        $csv = "従業員番号,6月分,7月分,8月分,9月分,10月分,11月分,12月分,1月分,2月分,3月分,4月分,5月分\n"
             . "GHOST,1,1,1,1,1,1,1,1,1,1,1,1\n";
        $r = (new ResidentTaxImporter())->import($this->tmpCsv($csv, 'SJIS-win'), 'saitama_city', 2026);
        $this->assertSame(0, $r['imported']);
        $this->assertSame(1, $r['skipped']);
        $this->assertContains('GHOST', $r['not_found']);
    }

    public function test_unknown_city_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new ResidentTaxImporter())->import($this->tmpCsv("a,b\n1,2"), 'no_such_city', 2026);
    }

    public function test_payroll_calculator_picks_up_imported_resident_tax(): void
    {
        $s = $this->makeStaff('PRT001');
        // Seed import for 2026-06
        $svc = new ResidentTaxImporter();
        $svc->import($this->tmpCsv(
            "従業員番号,6月分,7月分,8月分,9月分,10月分,11月分,12月分,1月分,2月分,3月分,4月分,5月分\nPRT001,7700,0,0,0,0,0,0,0,0,0,0,0\n",
            'SJIS-win'
        ), 'saitama_city', 2026);

        // Make staff that will accrue some pay in 2026-06
        $s->update(['hourly_rate' => 1500]);
        \Fujiba\Accounting\Models\Timecard::create([
            'staff_id' => $s->id, 'work_date' => '2026-06-15',
            'work_minutes' => 480, 'status' => 'approved',
        ]);

        $slip = app(\Fujiba\Accounting\Services\Payroll\PayrollCalculator::class)->calculate($s, '2026-06');
        $this->assertSame(7700, (int) $slip->resident_tax,
            'PayrollCalculator must read imported resident_tax from fujiba_settings');
    }
}
