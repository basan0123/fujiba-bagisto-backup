<?php

namespace Fujiba\Accounting\Services\Reports;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * 仕訳帳 — flat list of journal lines across all accounts, ordered by date.
 * Optional account_code filter narrows the result.
 *
 * Returns raw rows for CSV export; controller paginates the HTML list
 * separately (eloquent paginator on JournalEntry from Sprint 2).
 */
class JournalBookCalculator
{
    public function calculate(CarbonInterface $from, CarbonInterface $to, ?string $accountCode = null, ?int $channelId = null, int $limit = 5000): array
    {
        $q = DB::table('fujiba_journal_lines as jl')
            ->join('fujiba_journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('fujiba_accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('je.status', 'posted')
            ->whereBetween('je.entry_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('je.entry_date')
            ->orderBy('je.id')
            ->orderBy('jl.line_no')
            ->select([
                'je.entry_no', 'je.entry_date', 'je.memo as entry_memo', 'je.source_type', 'je.channel_id',
                'a.code as account_code', 'a.name_ja as account_name',
                'jl.debit', 'jl.credit', 'jl.memo as line_memo',
            ])
            ->limit($limit);

        if ($accountCode) {
            $q->where('a.code', $accountCode);
        }
        if ($channelId !== null) {
            $q->where('je.channel_id', $channelId);
        }

        $rows = [];
        $totalDr = 0;
        $totalCr = 0;
        foreach ($q->get() as $r) {
            $dr = (int) round((float) $r->debit);
            $cr = (int) round((float) $r->credit);
            $totalDr += $dr;
            $totalCr += $cr;
            $rows[] = [
                'entry_no'     => $r->entry_no,
                'entry_date'   => $r->entry_date,
                'source_type'  => $r->source_type,
                'channel_id'   => $r->channel_id,
                'account_code' => $r->account_code,
                'account_name' => $r->account_name,
                'memo'         => $r->line_memo ?: $r->entry_memo,
                'debit'        => $dr,
                'credit'       => $cr,
            ];
        }

        return [
            'from'         => $from->toDateString(),
            'to'           => $to->toDateString(),
            'rows'         => $rows,
            'total_debit'  => $totalDr,
            'total_credit' => $totalCr,
        ];
    }
}
