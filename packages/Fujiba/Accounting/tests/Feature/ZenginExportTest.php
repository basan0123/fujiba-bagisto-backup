<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Models\PayrollSlip;
use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Services\Payroll\ZenginExportService;
use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;

class ZenginExportTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeBankedStaff(array $overrides = []): Staff
    {
        return Staff::create(array_merge([
            'staff_code' => 'ZG-' . uniqid(),
            'qr_token'   => 'zg-' . uniqid(),
            'name_ja'    => 'Zengin テスト',
            'employment_type' => 'part_time',
            'is_active' => true,
            'bank_name'   => 'ミツビシＵＦＪ',           // mixed-width katakana on purpose
            'bank_code'   => '0005',
            'bank_branch' => 'シブヤシテン',
            'bank_branch_code' => '135',
            'bank_account_type'   => '普通',
            'bank_account_number' => '1234567',
            'bank_account_holder' => '山田 太郎',
            'bank_account_holder_kana' => 'ヤマダ タロウ',
        ], $overrides));
    }

    protected function makeSlip(Staff $s, int $netPay): PayrollSlip
    {
        return PayrollSlip::create([
            'staff_id'    => $s->id,
            'year_month'  => '2026-04',
            'pay_date'    => '2026-04-25',
            'channel_id'  => 1,
            'gross_pay'   => $netPay + 30000,
            'net_pay'     => $netPay,
            'total_deduction' => 30000,
            'status'      => 'finalized',
        ]);
    }

    protected function svc(): ZenginExportService
    {
        return new ZenginExportService();
    }

    protected function senderDefault(): array
    {
        return [
            'sender_code'  => '1234567890',
            'sender_name'  => 'カブシキガイシャフジバ',
            'transfer_date'=> '0425',
            'bank_code'    => '0001',
            'bank_name'    => 'ミズホ',
            'branch_code'  => '001',
            'branch_name'  => 'ホンテン',
            'account_type' => '1',
            'account_no'   => '0000001',
        ];
    }

    public function test_one_recipient_produces_4_records_correct_widths(): void
    {
        $s = $this->makeBankedStaff();
        $slips = collect([$this->makeSlip($s, 200000)]);

        $out = $this->svc()->build($slips, $this->senderDefault());
        $this->assertSame(1, $out['included']);
        $this->assertEmpty($out['errors']);

        // Output is already SJIS bytes — split on raw CRLF.
        $lines = explode("\r\n", trim($out['data'], "\r\n"));
        $this->assertCount(4, $lines);
        $this->assertSame('1', $lines[0][0]);
        $this->assertSame('2', $lines[1][0]);
        $this->assertSame('8', $lines[2][0]);
        $this->assertSame('9', $lines[3][0]);

        foreach ($lines as $i => $line) {
            $this->assertSame(120, strlen($line), "Line {$i} length not 120 bytes (sjis): " . strlen($line));
        }
    }

    public function test_multiple_recipients_trailer_totals(): void
    {
        $s1 = $this->makeBankedStaff(['staff_code' => 'ZG-A-' . uniqid()]);
        $s2 = $this->makeBankedStaff(['staff_code' => 'ZG-B-' . uniqid()]);
        $slips = collect([
            $this->makeSlip($s1, 100000),
            $this->makeSlip($s2, 250000),
        ]);

        $out = $this->svc()->build($slips, $this->senderDefault());
        $this->assertSame(2, $out['included']);
        $this->assertSame(['count' => 2, 'amount' => 350000], $out['totals']);

        $lines = explode("\r\n", trim($out['data'], "\r\n"));
        // header(1) + 2 data + trailer + end = 5 lines; trailer is index 3
        $this->assertCount(5, $lines);
        $trailer = $lines[3];
        $this->assertSame('8', $trailer[0]);
        $this->assertSame('000002', substr($trailer, 1, 6));
        $this->assertSame('000000350000', substr($trailer, 7, 12));
    }

    public function test_kana_normalization_full_to_half(): void
    {
        $s = $this->makeBankedStaff(['bank_account_holder_kana' => 'ヤマダ タロウ']); // full-width
        $slips = collect([$this->makeSlip($s, 100000)]);

        $out = $this->svc()->build($slips, $this->senderDefault());
        $lines = explode("\r\n", trim($out['data'], "\r\n"));
        $dataRecord = $lines[1];
        // Account holder is at offset 50, width 30 bytes
        $holderField = substr($dataRecord, 50, 30);

        // The holder field is SJIS half-kana bytes. Decode to UTF-8 to inspect.
        $holderUtf8 = mb_convert_encoding($holderField, 'UTF-8', 'SJIS-win');
        $this->assertStringNotContainsString('ヤ', $holderUtf8, 'Full-width katakana must be converted to half-width');
        $this->assertStringContainsString('ﾔﾏﾀﾞ', $holderUtf8);
    }

    public function test_amount_zero_padded_to_10_digits(): void
    {
        $s = $this->makeBankedStaff();
        $slips = collect([$this->makeSlip($s, 1500)]);

        $out = $this->svc()->build($slips, $this->senderDefault());
        $lines = explode("\r\n", trim($out['data'], "\r\n"));
        $dataRecord = $lines[1];
        // Amount field: 10 bytes, after holder name (offset 80)
        $amountField = substr($dataRecord, 80, 10);
        $this->assertSame('0000001500', $amountField);
    }

    public function test_missing_bank_codes_excluded_with_error(): void
    {
        $s = Staff::create([
            'staff_code' => 'ZG-MISS-' . uniqid(),
            'qr_token'   => 'zg-miss-' . uniqid(),
            'name_ja'    => 'No Bank',
            'employment_type' => 'part_time',
            'is_active' => true,
            // Intentionally no bank_code / branch_code / account_no / holder_kana
        ]);
        $slips = collect([$this->makeSlip($s, 100000)]);

        $out = $this->svc()->build($slips, $this->senderDefault());
        $this->assertSame(0, $out['included']);
        $this->assertNotEmpty($out['errors']);
        $this->assertStringContainsString('bank_code', implode(' ', $out['errors']));
    }

    public function test_negative_or_zero_pay_excluded(): void
    {
        $s = $this->makeBankedStaff();
        $slips = collect([$this->makeSlip($s, 0)]);

        $out = $this->svc()->build($slips, $this->senderDefault());
        $this->assertSame(0, $out['included']);
        $this->assertStringContainsString('skipped', implode(' ', $out['errors']));
    }

    public function test_route_streams_sjis_payload(): void
    {
        $admin = \Webkul\User\Models\Admin::query()->first();
        if (! $admin) { $this->markTestSkipped('no admin'); }
        $this->actingAs($admin, 'admin');

        $s = $this->makeBankedStaff();
        $this->makeSlip($s, 200000);

        $resp = $this->get(route('admin.accounting.payroll.index', [
            'year_month' => '2026-04',
            'format'     => 'zengin',
        ]));
        $resp->assertStatus(200);
        $this->assertStringStartsWith('text/plain', $resp->headers->get('Content-Type'));
    }
}
