<?php

namespace Fujiba\Accounting\Services\Reports;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Per-account totals over a date range, grouped from journal_lines.
 * Returns an array of rows ordered by account.code, plus totals.
 *
 *   row = [
 *     'code' => '1110', 'name_ja' => '現金', 'type' => 'asset',
 *     'sub_type' => 'current_asset',
 *     'debit' => int, 'credit' => int, 'balance' => int (signed by type),
 *   ]
 */
class TrialBalanceCalculator
{
    public function calculate(CarbonInterface $from, CarbonInterface $to, ?int $channelId = null): array
    {
        $q = DB::table('fujiba_journal_lines as jl')
            ->join('fujiba_journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('fujiba_accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('je.status', 'posted')
            ->whereBetween('je.entry_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('a.id', 'a.code', 'a.name_ja', 'a.type', 'a.sub_type')
            ->orderBy('a.code')
            ->select([
                'a.code',
                'a.name_ja',
                'a.type',
                'a.sub_type',
                DB::raw('COALESCE(SUM(jl.debit), 0)  AS dr'),
                DB::raw('COALESCE(SUM(jl.credit), 0) AS cr'),
            ]);

        if ($channelId !== null) {
            $q->where('je.channel_id', $channelId);
        }

        $rows = [];
        $grandDr = 0;
        $grandCr = 0;

        foreach ($q->get() as $r) {
            $dr = (int) round((float) $r->dr);
            $cr = (int) round((float) $r->cr);
            // Balance sign: assets/expense = DR-positive; liability/equity/revenue = CR-positive.
            $balance = match ($r->type) {
                'asset', 'expense' => $dr - $cr,
                default            => $cr - $dr,
            };

            $rows[] = [
                'code'     => $r->code,
                'name_ja'  => $r->name_ja,
                'type'     => $r->type,
                'sub_type' => $r->sub_type,
                'debit'    => $dr,
                'credit'   => $cr,
                'balance'  => $balance,
            ];
            $grandDr += $dr;
            $grandCr += $cr;
        }

        return [
            'rows'         => $rows,
            'total_debit'  => $grandDr,
            'total_credit' => $grandCr,
            'balanced'     => $grandDr === $grandCr,
            'from'         => $from->toDateString(),
            'to'           => $to->toDateString(),
        ];
    }
}
