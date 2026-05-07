<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Models\Account;
use Fujiba\Accounting\Models\Receipt;
use Fujiba\Accounting\Services\ReceiptJournalBuilder;
use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ReceiptJournalBuilderTest extends TestCase
{
    use DatabaseTransactions;

    protected function builder(): ReceiptJournalBuilder
    {
        return app(ReceiptJournalBuilder::class);
    }

    public function test_cash_receipt_credits_genkin_and_books_kariharai_shouhizei(): void
    {
        $account = Account::where('code', '6260')->first();   // 消耗品費
        $r = new Receipt([
            'channel_id'      => 1,
            'receipt_date'    => '2026-05-08',
            'vendor_name'     => 'テスト商店',
            'total_amount'    => 1100,
            'tax_amount'      => 100,
            'tax_category'    => 'standard',
            'payment_method'  => 'cash',
            'account_id'      => $account->id,
        ]);
        $r->id = 999;

        $payload = $this->builder()->build($r);
        $lines = collect($payload['lines']);

        $this->assertSame(1100, (int) $lines->sum('debit'));
        $this->assertSame(1100, (int) $lines->sum('credit'));

        $this->assertSame('6260', $lines[0]['account_code']);   // expense (net)
        $this->assertSame(1000, (int) $lines[0]['debit']);

        $this->assertSame('1220', $lines[1]['account_code']);   // 仮払消費税
        $this->assertSame(100, (int) $lines[1]['debit']);

        $this->assertSame('1110', $lines[2]['account_code']);   // 現金
        $this->assertSame(1100, (int) $lines[2]['credit']);
    }

    public function test_non_cash_receipt_credits_miharaikin(): void
    {
        $account = Account::where('code', '6220')->first();   // 通信費
        $r = new Receipt([
            'channel_id'      => 1,
            'receipt_date'    => '2026-05-08',
            'vendor_name'     => 'NTT',
            'total_amount'    => 5500,
            'tax_amount'      => 500,
            'tax_category'    => 'standard',
            'payment_method'  => 'bank_transfer',
            'account_id'      => $account->id,
        ]);
        $r->id = 998;

        $payload = $this->builder()->build($r);
        $lines = collect($payload['lines']);
        $credit = $lines->firstWhere('credit', '>', 0);

        $this->assertSame('2130', $credit['account_code']);   // 未払金
        $this->assertSame(5500, (int) $credit['credit']);
    }

    public function test_zero_tax_skips_kariharai_shouhizei_line(): void
    {
        $account = Account::where('code', '6280')->first();   // 地代家賃 (non-taxable)
        $r = new Receipt([
            'channel_id'      => 1,
            'receipt_date'    => '2026-05-08',
            'vendor_name'     => 'オーナー',
            'total_amount'    => 80000,
            'tax_amount'      => 0,
            'tax_category'    => 'non_taxable',
            'payment_method'  => 'bank_transfer',
            'account_id'      => $account->id,
        ]);
        $r->id = 997;

        $payload = $this->builder()->build($r);
        $lines = collect($payload['lines']);

        $this->assertCount(2, $lines, 'No tax line expected for non-taxable receipt');
        $codes = $lines->pluck('account_code')->all();
        $this->assertNotContains('1220', $codes);
        $this->assertSame(80000, (int) $lines->sum('debit'));
        $this->assertSame(80000, (int) $lines->sum('credit'));
    }

    public function test_falls_back_to_zatsuhi_when_no_account_selected(): void
    {
        $r = new Receipt([
            'channel_id'      => 1,
            'receipt_date'    => '2026-05-08',
            'vendor_name'     => '名無し',
            'total_amount'    => 1100,
            'tax_amount'      => 100,
            'tax_category'    => 'standard',
            'payment_method'  => 'cash',
        ]);
        $r->id = 996;

        $payload = $this->builder()->build($r);
        $debit = collect($payload['lines'])->firstWhere('debit', '>', 0);
        $this->assertSame('6390', $debit['account_code']);
    }
}
