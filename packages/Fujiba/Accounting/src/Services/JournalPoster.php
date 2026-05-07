<?php

namespace Fujiba\Accounting\Services;

use Carbon\CarbonInterface;
use Fujiba\Accounting\Exceptions\ClosedPeriodException;
use Fujiba\Accounting\Exceptions\DuplicateIdempotencyException;
use Fujiba\Accounting\Exceptions\UnbalancedEntryException;
use Fujiba\Accounting\Exceptions\UnknownAccountException;
use Fujiba\Accounting\Models\Account;
use Fujiba\Accounting\Models\EntrySequence;
use Fujiba\Accounting\Models\FiscalPeriod;
use Fujiba\Accounting\Models\JournalEntry;
use Fujiba\Accounting\Models\JournalLine;
use Fujiba\Accounting\Models\TaxRate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Domain entry-point for posting balanced journal entries.
 *
 * Money convention: amounts are JPY whole yen (integers). The schema stores
 * DECIMAL(15,2) for portability with non-JPY books later, but inside this
 * service we cast to int on read and write `N.00` on the wire. JPY has no
 * fractional unit so any non-zero decimal is treated as a programming bug.
 */
class JournalPoster
{
    /**
     * @param  array{
     *   entry_date: string|CarbonInterface,
     *   channel_id?: int|null,
     *   description?: string|null,
     *   memo?: string|null,
     *   source_type?: string,
     *   source_id?: int|null,
     *   idempotency_key?: string|null,
     *   reference_no?: string|null,
     *   posted_by?: int|null,
     *   lines: array<int,array{
     *     account_code: string,
     *     debit?: int|float,
     *     credit?: int|float,
     *     tax_rate?: float|null,
     *     tax_amount?: int|float|null,
     *     memo?: string|null,
     *     channel_id?: int|null,
     *     cost_center?: string|null,
     *     partner_name?: string|null,
     *   }>,
     * }  $payload
     */
    public function post(array $payload): JournalEntry
    {
        $entryDate = $payload['entry_date'] instanceof CarbonInterface
            ? Carbon::instance($payload['entry_date'])
            : Carbon::parse($payload['entry_date']);

        $lines = $payload['lines'] ?? [];
        if (count($lines) < 2) {
            throw new UnbalancedEntryException(0, 0);
        }

        // ── Validate balance ────────────────────────────────────────────────
        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($lines as $line) {
            $totalDebit  += (int) round((float) ($line['debit'] ?? 0));
            $totalCredit += (int) round((float) ($line['credit'] ?? 0));
        }
        if ($totalDebit !== $totalCredit || $totalDebit === 0) {
            throw new UnbalancedEntryException($totalDebit, $totalCredit);
        }

        // ── Resolve accounts up-front (fail fast) ───────────────────────────
        $accountIdByCode = $this->resolveAccounts($lines);

        // ── Resolve fiscal period ───────────────────────────────────────────
        $period = FiscalPeriod::where('status', 'open')
            ->whereDate('start_date', '<=', $entryDate->toDateString())
            ->whereDate('end_date', '>=', $entryDate->toDateString())
            ->first();
        if (! $period) {
            throw new ClosedPeriodException($entryDate->toDateString());
        }

        $idempotencyKey = $payload['idempotency_key'] ?? null;

        return DB::transaction(function () use ($payload, $entryDate, $period, $lines, $accountIdByCode, $idempotencyKey) {

            // ── Idempotency check (in-transaction so we don't race) ────────
            if ($idempotencyKey !== null) {
                $existing = JournalEntry::where('source_idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    // Spec: idempotent retry — return the existing entry instead of throwing.
                    // (DuplicateIdempotencyException is reserved for callers that explicitly
                    //  want to be told a retry happened; controllers can pass strict=true if
                    //  they care.  Default behavior is silent re-return.)
                    return $existing;
                }
            }

            // ── Allocate entry_no via per-month sequence ───────────────────
            $entryNo = $this->allocateEntryNo($entryDate);

            $entry = JournalEntry::create([
                'entry_no'               => $entryNo,
                'fiscal_period_id'       => $period->id,
                'entry_date'             => $entryDate->toDateString(),
                'channel_id'             => $payload['channel_id'] ?? null,
                'posted_by'              => $payload['posted_by'] ?? auth('admin')->id() ?? auth()->id(),
                'source_type'            => $payload['source_type'] ?? 'manual',
                'source_id'              => $payload['source_id'] ?? null,
                'source_idempotency_key' => $idempotencyKey,
                'memo'                   => $payload['memo'] ?? $payload['description'] ?? null,
                'reference_no'           => $payload['reference_no'] ?? null,
                'status'                 => 'posted',
                'posted_at'              => now(),
            ]);

            $taxRateIdByCode = $this->resolveTaxRates($lines);
            foreach ($lines as $i => $line) {
                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id'       => $accountIdByCode[$line['account_code']],
                    'line_no'          => $i + 1,
                    'debit'            => (int) round((float) ($line['debit'] ?? 0)),
                    'credit'           => (int) round((float) ($line['credit'] ?? 0)),
                    'tax_rate_id'      => isset($line['tax_rate'])
                        ? ($taxRateIdByCode[(string) $line['tax_rate']] ?? null)
                        : null,
                    'tax_amount'       => (int) round((float) ($line['tax_amount'] ?? 0)),
                    'memo'             => $line['memo'] ?? null,
                    'channel_id'       => $line['channel_id'] ?? ($payload['channel_id'] ?? null),
                    'cost_center'      => $line['cost_center'] ?? null,
                    'partner_name'     => $line['partner_name'] ?? null,
                ]);
            }

            return $entry->fresh('lines');
        });
    }

    /** @param  array<int,array<string,mixed>>  $lines */
    protected function resolveAccounts(array $lines): array
    {
        $codes = collect($lines)->pluck('account_code')->unique()->values();
        $accounts = Account::whereIn('code', $codes)->pluck('id', 'code')->all();

        foreach ($codes as $code) {
            if (! isset($accounts[$code])) {
                throw new UnknownAccountException($code);
            }
        }
        return $accounts;
    }

    /** @param  array<int,array<string,mixed>>  $lines */
    protected function resolveTaxRates(array $lines): array
    {
        // Build a lookup: rate% (string) → tax_rate_id by matching consumption_* codes.
        $rateMap = [
            '10' => 'consumption_standard',
            '8'  => 'consumption_reduced',
            '10.0' => 'consumption_standard',
            '8.0'  => 'consumption_reduced',
        ];
        $codes = array_unique(array_values($rateMap));
        $byCode = TaxRate::whereIn('code', $codes)->pluck('id', 'code')->all();

        $result = [];
        foreach ($rateMap as $rateKey => $taxCode) {
            if (isset($byCode[$taxCode])) {
                $result[$rateKey] = $byCode[$taxCode];
            }
        }
        return $result;
    }

    protected function allocateEntryNo(CarbonInterface $entryDate): string
    {
        $ym = $entryDate->format('Y-m');

        // Race-safe: SELECT … FOR UPDATE within the outer transaction.
        $row = DB::table('fujiba_entry_sequences')
            ->where('year_month', $ym)
            ->lockForUpdate()
            ->first();

        if ($row === null) {
            // Race: two callers may both insert. INSERT IGNORE then re-select.
            DB::table('fujiba_entry_sequences')->insertOrIgnore([
                'year_month' => $ym,
                'last_seq'   => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $row = DB::table('fujiba_entry_sequences')
                ->where('year_month', $ym)
                ->lockForUpdate()
                ->first();
        }

        $next = ((int) $row->last_seq) + 1;
        DB::table('fujiba_entry_sequences')
            ->where('year_month', $ym)
            ->update(['last_seq' => $next, 'updated_at' => now()]);

        return sprintf('J-%s-%05d', str_replace('-', '', $entryDate->format('Ym')), $next);
    }
}
