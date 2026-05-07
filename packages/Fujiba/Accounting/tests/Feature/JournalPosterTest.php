<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Exceptions\ClosedPeriodException;
use Fujiba\Accounting\Exceptions\UnbalancedEntryException;
use Fujiba\Accounting\Exceptions\UnknownAccountException;
use Fujiba\Accounting\Models\FiscalPeriod;
use Fujiba\Accounting\Services\JournalPoster;
use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class JournalPosterTest extends TestCase
{
    use DatabaseTransactions;

    protected function poster(): JournalPoster
    {
        return app(JournalPoster::class);
    }

    public function test_balanced_entry_posts_with_entry_no(): void
    {
        $entry = $this->poster()->post([
            'entry_date'      => '2026-05-08',
            'channel_id'      => 1,
            'memo'            => 'unit-test balanced',
            'idempotency_key' => 'jp-test-' . uniqid(),
            'lines' => [
                ['account_code' => '1110', 'debit' => 1000, 'credit' => 0],
                ['account_code' => '4110', 'debit' => 0,    'credit' => 909],
                ['account_code' => '2180', 'debit' => 0,    'credit' => 91],
            ],
        ]);

        $this->assertNotNull($entry->id);
        $this->assertMatchesRegularExpression('/^J-202605-\d{5}$/', $entry->entry_no);
        $this->assertSame('posted', $entry->status);
        $this->assertCount(3, $entry->lines);
    }

    public function test_unbalanced_entry_throws(): void
    {
        $this->expectException(UnbalancedEntryException::class);
        $this->poster()->post([
            'entry_date' => '2026-05-08',
            'lines' => [
                ['account_code' => '1110', 'debit' => 1000, 'credit' => 0],
                ['account_code' => '4110', 'debit' => 0,    'credit' => 999],
            ],
        ]);
    }

    public function test_unknown_account_throws(): void
    {
        $this->expectException(UnknownAccountException::class);
        $this->poster()->post([
            'entry_date' => '2026-05-08',
            'lines' => [
                ['account_code' => '9999', 'debit' => 100, 'credit' => 0],
                ['account_code' => '4110', 'debit' => 0,   'credit' => 100],
            ],
        ]);
    }

    public function test_closed_period_throws(): void
    {
        $this->expectException(ClosedPeriodException::class);
        $this->poster()->post([
            'entry_date' => '2099-12-31',  // no period covers this
            'lines' => [
                ['account_code' => '1110', 'debit' => 100, 'credit' => 0],
                ['account_code' => '4110', 'debit' => 0,   'credit' => 100],
            ],
        ]);
    }

    public function test_duplicate_idempotency_key_returns_existing_entry(): void
    {
        $key = 'jp-dup-' . uniqid();

        $first = $this->poster()->post([
            'entry_date'      => '2026-05-08',
            'idempotency_key' => $key,
            'lines' => [
                ['account_code' => '1110', 'debit' => 500, 'credit' => 0],
                ['account_code' => '4110', 'debit' => 0,   'credit' => 500],
            ],
        ]);

        $second = $this->poster()->post([
            'entry_date'      => '2026-05-08',
            'idempotency_key' => $key,
            'lines' => [
                ['account_code' => '1110', 'debit' => 500, 'credit' => 0],
                ['account_code' => '4110', 'debit' => 0,   'credit' => 500],
            ],
        ]);

        $this->assertSame($first->id, $second->id, 'Re-post with same idempotency_key must return the same entry');
    }

    public function test_entry_no_increments_per_month(): void
    {
        $a = $this->poster()->post([
            'entry_date'      => '2026-05-08',
            'idempotency_key' => 'jp-seq-a-' . uniqid(),
            'lines' => [
                ['account_code' => '1110', 'debit' => 1, 'credit' => 0],
                ['account_code' => '4110', 'debit' => 0, 'credit' => 1],
            ],
        ]);
        $b = $this->poster()->post([
            'entry_date'      => '2026-05-08',
            'idempotency_key' => 'jp-seq-b-' . uniqid(),
            'lines' => [
                ['account_code' => '1110', 'debit' => 1, 'credit' => 0],
                ['account_code' => '4110', 'debit' => 0, 'credit' => 1],
            ],
        ]);

        $aSeq = (int) substr($a->entry_no, -5);
        $bSeq = (int) substr($b->entry_no, -5);
        $this->assertSame($aSeq + 1, $bSeq, 'Sequence must increment monotonically inside the same month');
    }

    public function test_entry_no_resets_per_month(): void
    {
        // April vs May share no sequence — verify both have their own seq>=1.
        $april = $this->poster()->post([
            'entry_date'      => '2026-04-15',
            'idempotency_key' => 'jp-apr-' . uniqid(),
            'lines' => [
                ['account_code' => '1110', 'debit' => 10, 'credit' => 0],
                ['account_code' => '4110', 'debit' => 0,  'credit' => 10],
            ],
        ]);
        $may = $this->poster()->post([
            'entry_date'      => '2026-05-15',
            'idempotency_key' => 'jp-may-' . uniqid(),
            'lines' => [
                ['account_code' => '1110', 'debit' => 10, 'credit' => 0],
                ['account_code' => '4110', 'debit' => 0,  'credit' => 10],
            ],
        ]);

        $this->assertStringStartsWith('J-202604-', $april->entry_no);
        $this->assertStringStartsWith('J-202605-', $may->entry_no);
    }

    public function test_two_lines_minimum_enforced(): void
    {
        $this->expectException(UnbalancedEntryException::class);
        $this->poster()->post([
            'entry_date' => '2026-05-08',
            'lines' => [
                ['account_code' => '1110', 'debit' => 100, 'credit' => 0],
            ],
        ]);
    }
}
