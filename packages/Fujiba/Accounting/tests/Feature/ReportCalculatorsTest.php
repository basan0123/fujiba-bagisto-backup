<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Services\JournalPoster;
use Fujiba\Accounting\Services\Reports\BsCalculator;
use Fujiba\Accounting\Services\Reports\JournalBookCalculator;
use Fujiba\Accounting\Services\Reports\LedgerCalculator;
use Fujiba\Accounting\Services\Reports\PlCalculator;
use Fujiba\Accounting\Services\Reports\TrialBalanceCalculator;
use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;

/**
 * End-to-end calculator coverage. Posts a known set of entries via
 * JournalPoster, then asserts every report produces the expected numbers.
 */
class ReportCalculatorsTest extends TestCase
{
    use DatabaseTransactions;

    protected function poster(): JournalPoster
    {
        return app(JournalPoster::class);
    }

    /** Seed a small but representative book of entries inside the open period. */
    protected function seedBook(): void
    {
        // Sale ¥1100 cash (10% tax) on 2026-04-10
        $this->poster()->post([
            'entry_date'      => '2026-04-10',
            'channel_id'      => 1,
            'idempotency_key' => 'tb-sale-' . uniqid(),
            'lines' => [
                ['account_code' => '1110', 'debit' => 1100, 'credit' => 0],   // 現金
                ['account_code' => '4110', 'debit' => 0,    'credit' => 1000],// 売上 10%
                ['account_code' => '2180', 'debit' => 0,    'credit' => 100], // 仮受消費税
            ],
        ]);

        // Purchase ¥550 cash (cogs) on 2026-04-12
        $this->poster()->post([
            'entry_date'      => '2026-04-12',
            'channel_id'      => 1,
            'idempotency_key' => 'tb-purch-' . uniqid(),
            'lines' => [
                ['account_code' => '5110', 'debit' => 500, 'credit' => 0],   // 仕入
                ['account_code' => '1220', 'debit' => 50,  'credit' => 0],   // 仮払消費税
                ['account_code' => '1110', 'debit' => 0,   'credit' => 550], // 現金
            ],
        ]);

        // Rent expense ¥80,000 cash on 2026-04-15
        $this->poster()->post([
            'entry_date'      => '2026-04-15',
            'channel_id'      => 1,
            'idempotency_key' => 'tb-rent-' . uniqid(),
            'lines' => [
                ['account_code' => '6280', 'debit' => 80000, 'credit' => 0], // 地代家賃
                ['account_code' => '1110', 'debit' => 0,     'credit' => 80000],
            ],
        ]);

        // Capital injection ¥1,000,000 (opening) on 2026-04-01
        $this->poster()->post([
            'entry_date'      => '2026-04-01',
            'channel_id'      => null,
            'idempotency_key' => 'tb-capital-' . uniqid(),
            'lines' => [
                ['account_code' => '1130', 'debit' => 1000000, 'credit' => 0], // 普通預金
                ['account_code' => '3110', 'debit' => 0,       'credit' => 1000000], // 資本金
            ],
        ]);
    }

    public function test_trial_balance_aggregates_correctly(): void
    {
        $this->seedBook();

        $tb = app(TrialBalanceCalculator::class)
            ->calculate(Carbon::parse('2026-04-01'), Carbon::parse('2026-04-30'));

        $this->assertTrue($tb['balanced'], 'trial balance must balance — DR sum == CR sum');
        $byCode = collect($tb['rows'])->keyBy('code');

        // 現金: DR 1100 (sale) − CR 550 (purchase) − CR 80000 (rent) = -79450
        $this->assertSame(1100, (int) $byCode['1110']['debit']);
        $this->assertSame(80550, (int) $byCode['1110']['credit']);
        $this->assertSame(-79450, (int) $byCode['1110']['balance']);

        // 売上: CR 1000 → balance +1000 (revenue: cr-positive)
        $this->assertSame(1000, (int) $byCode['4110']['balance']);

        // 仕入: DR 500 → balance +500 (expense: dr-positive)
        $this->assertSame(500, (int) $byCode['5110']['balance']);

        // 資本金: CR 1,000,000 → balance +1,000,000
        $this->assertSame(1000000, (int) $byCode['3110']['balance']);
    }

    public function test_pl_groups_by_sub_type_and_computes_intermediate_totals(): void
    {
        $this->seedBook();

        $pl = app(PlCalculator::class)
            ->calculate(Carbon::parse('2026-04-01'), Carbon::parse('2026-04-30'));

        $s = $pl['sections'];
        $this->assertSame(1000,    $s['sales']['total']);
        $this->assertSame(500,     $s['cogs']['total']);
        $this->assertSame(500,     $s['gross_profit']['total']);
        $this->assertSame(80000,   $s['sga']['total']);
        $this->assertSame(-79500,  $s['operating']['total']);
        $this->assertSame(-79500,  $s['ordinary']['total']);
        $this->assertSame(-79500,  $s['pretax']['total']);
    }

    public function test_bs_balances_assets_to_liabilities_plus_equity(): void
    {
        $this->seedBook();

        $bs = app(BsCalculator::class)
            ->calculate(Carbon::parse('2026-04-30'));

        $this->assertTrue($bs['balanced'],
            "BS balance check: assets={$bs['assets']['total']} liab+eq={$bs['total_liab_and_equity']}");

        // Assets: 普通預金 1,000,000 + 現金 −79,450 + 仮払消費税 50 = 920,600
        $this->assertSame(920600, (int) $bs['assets']['total']);

        // Equity: 資本金 1,000,000 + 当期純損 −79,500 + 仮受消費税 (liability side, not equity) = 920,500
        $this->assertSame(1000000 + (-79500), $bs['equity']['total']);

        // Liabilities: 仮受消費税 100
        $this->assertSame(100, (int) $bs['liabilities']['total']);
    }

    public function test_ledger_walks_running_balance(): void
    {
        $this->seedBook();

        $ledger = app(LedgerCalculator::class)
            ->calculate('1110', Carbon::parse('2026-04-01'), Carbon::parse('2026-04-30'));

        $this->assertSame(0, $ledger['opening']);
        $this->assertSame(-79450, $ledger['closing']);
        $this->assertSame(1100,  $ledger['period_debit']);
        $this->assertSame(80550, $ledger['period_credit']);
        $this->assertSame(3, count($ledger['rows']));
    }

    public function test_journal_book_lists_all_lines(): void
    {
        $this->seedBook();

        $book = app(JournalBookCalculator::class)
            ->calculate(Carbon::parse('2026-04-01'), Carbon::parse('2026-04-30'));

        // 4 entries × variable lines = 3+3+2+2 = 10 lines total
        $this->assertCount(10, $book['rows']);
        $this->assertSame($book['total_debit'], $book['total_credit'], 'journal book DR sum must equal CR sum');
    }

    public function test_journal_book_account_filter(): void
    {
        $this->seedBook();

        $book = app(JournalBookCalculator::class)
            ->calculate(Carbon::parse('2026-04-01'), Carbon::parse('2026-04-30'), '1110');

        // 現金 appears in 3 entries, once each
        $this->assertCount(3, $book['rows']);
        foreach ($book['rows'] as $r) {
            $this->assertSame('1110', $r['account_code']);
        }
    }

    public function test_ledger_unknown_account_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(LedgerCalculator::class)->calculate('9999', Carbon::parse('2026-04-01'), Carbon::parse('2026-04-30'));
    }
}
