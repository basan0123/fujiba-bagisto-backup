<?php

namespace Fujiba\Accounting\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Fujiba\Accounting\Models\Account;
use Fujiba\Accounting\Services\Reports\BsCalculator;
use Fujiba\Accounting\Services\Reports\JournalBookCalculator;
use Fujiba\Accounting\Services\Reports\LedgerCalculator;
use Fujiba\Accounting\Services\Reports\PlCalculator;
use Fujiba\Accounting\Services\Reports\ReportPeriodResolver;
use Fujiba\Accounting\Services\Reports\TrialBalanceCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Renders all five Sprint-5 reports (HTML / PDF / CSV).
 *
 * Caching: every calculator result is wrapped in Cache::remember keyed by
 * {report}:{params}. TTL comes from accounting.dashboard.cache_ttl_seconds.
 * Pass `?nocache=1` on any URL to bypass.
 */
class ReportController extends Controller
{
    public function __construct(
        protected ReportPeriodResolver $period,
        protected TrialBalanceCalculator $tb,
        protected PlCalculator $pl,
        protected BsCalculator $bs,
        protected LedgerCalculator $ledger,
        protected JournalBookCalculator $book,
    ) {}

    // ───────────────────────────────────────────── 試算表
    public function trialBalance(Request $request)
    {
        $params  = $request->only(['year_month', 'fiscal_year', 'from', 'to', 'channel_id']);
        $period  = $this->period->resolve($params);
        $channel = $this->channel($request);
        $data    = $this->cached('trial-balance', $params, $request,
            fn () => $this->tb->calculate($period['from'], $period['to'], $channel));

        $payload = ['data' => $data, 'period' => $period, 'params' => $params];

        return $this->respond($request, 'trial-balance', $payload, function () use ($payload) {
            return $this->csvFromTrialBalance($payload);
        });
    }

    // ───────────────────────────────────────────── 損益計算書 (P/L)
    public function pl(Request $request)
    {
        $params  = $request->only(['year_month', 'fiscal_year', 'from', 'to', 'channel_id']);
        $period  = $this->period->resolve($params);
        $channel = $this->channel($request);
        $data    = $this->cached('pl', $params, $request,
            fn () => $this->pl->calculate($period['from'], $period['to'], $channel));

        $payload = ['data' => $data, 'period' => $period, 'params' => $params];

        return $this->respond($request, 'pl', $payload, fn () => $this->csvFromPl($payload));
    }

    // ───────────────────────────────────────────── 貸借対照表 (B/S)
    public function bs(Request $request)
    {
        $asOf    = $request->filled('as_of') ? Carbon::parse($request->input('as_of'))->endOfDay() : Carbon::now()->endOfDay();
        $params  = ['as_of' => $asOf->toDateString()] + $request->only(['channel_id']);
        $channel = $this->channel($request);
        $data    = $this->cached('bs', $params, $request,
            fn () => $this->bs->calculate($asOf, $channel));

        $payload = [
            'data'   => $data,
            'period' => ['from' => $asOf, 'to' => $asOf, 'label' => $asOf->toDateString()],
            'params' => $params,
        ];

        return $this->respond($request, 'bs', $payload, fn () => $this->csvFromBs($payload));
    }

    // ───────────────────────────────────────────── 仕訳帳 (CSV-first)
    public function journalBook(Request $request)
    {
        $params  = $request->only(['from', 'to', 'account_code', 'channel_id']);
        $period  = $this->period->resolve($params);
        $channel = $this->channel($request);
        $accountCode = $request->input('account_code') ?: null;
        $data    = $this->cached('journal-book', $params, $request,
            fn () => $this->book->calculate($period['from'], $period['to'], $accountCode, $channel));

        $payload = ['data' => $data, 'period' => $period, 'params' => $params, 'account_code' => $accountCode];

        // PDF disabled by spec for journal-book.
        if ($request->input('format') === 'pdf') {
            abort(400, 'PDF export not supported for journal book — use CSV.');
        }
        if ($request->input('format') === 'csv') {
            return $this->csvFromJournalBook($payload);
        }
        return view('accounting::admin.reports.html.journal-book', $payload + [
            'accounts' => $this->accountList(),
        ]);
    }

    // ───────────────────────────────────────────── 総勘定元帳
    public function ledger(Request $request)
    {
        $request->validate([
            'account_code' => ['required', 'string', 'max:8'],
        ]);

        $params  = $request->only(['account_code', 'from', 'to', 'channel_id']);
        $period  = $this->period->resolve($params);
        $channel = $this->channel($request);

        try {
            $data = $this->cached('ledger', $params, $request,
                fn () => $this->ledger->calculate($params['account_code'], $period['from'], $period['to'], $channel));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['account_code' => $e->getMessage()]);
        }

        $payload = ['data' => $data, 'period' => $period, 'params' => $params];

        return $this->respond($request, 'ledger', $payload, fn () => $this->csvFromLedger($payload));
    }

    // ───────────────────────────────────────────── helpers

    protected function channel(Request $r): ?int
    {
        return $r->filled('channel_id') ? (int) $r->input('channel_id') : null;
    }

    protected function cached(string $name, array $params, Request $r, \Closure $compute): mixed
    {
        if ($r->boolean('nocache')) {
            return $compute();
        }
        $ttl = (int) config('accounting.dashboard.cache_ttl_seconds', 3600);
        if ($ttl <= 0) {
            return $compute();
        }
        $key = config('accounting.dashboard.cache_prefix', 'fujiba:accounting:report:')
             . $name . ':' . md5(json_encode($params));
        return Cache::remember($key, $ttl, $compute);
    }

    protected function respond(Request $request, string $name, array $payload, \Closure $csvBuilder)
    {
        $format = $request->input('format');
        if ($format === 'pdf') {
            return $this->pdf($name, $payload);
        }
        if ($format === 'csv') {
            return $csvBuilder();
        }
        return view("accounting::admin.reports.html.{$name}", $payload + [
            'accounts' => $this->accountList(),
        ]);
    }

    protected function pdf(string $name, array $payload): Response
    {
        $pdf = Pdf::loadView("accounting::admin.reports.pdf.{$name}", $payload + [
            'generatedAt' => now(),
            'companyName' => config('app.name'),
        ])->setPaper('A4', 'portrait')->set_option('defaultFont', 'DejaVu Sans');

        $filename = "{$name}_{$this->labelForFile($payload['period'] ?? null)}.pdf";
        return $pdf->download($filename);
    }

    protected function labelForFile(?array $period): string
    {
        if (! $period) return now()->format('Ymd');
        $label = $period['label'] ?? null;
        return $label ? str_replace([' ', '→', '/'], ['_', '-', '-'], $label) : now()->format('Ymd');
    }

    protected function accountList()
    {
        return Account::orderBy('code')->get(['id', 'code', 'name_ja', 'type', 'sub_type']);
    }

    // ───────────────────────────────────────────── CSV builders

    protected function csvDownload(string $filename, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            // UTF-8 BOM for Excel
            echo "\xEF\xBB\xBF";
            $h = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($h, array_map(fn ($v) => is_int($v) ? $v : (string) $v, $row));
            }
            fclose($h);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function csvFromTrialBalance(array $payload): StreamedResponse
    {
        $d = $payload['data'];
        $rows = [['code', 'name_ja', 'type', 'sub_type', 'debit', 'credit', 'balance']];
        foreach ($d['rows'] as $r) {
            $rows[] = [$r['code'], $r['name_ja'], $r['type'], $r['sub_type'], $r['debit'], $r['credit'], $r['balance']];
        }
        $rows[] = ['', 'TOTAL', '', '', $d['total_debit'], $d['total_credit'], $d['total_debit'] - $d['total_credit']];
        return $this->csvDownload('trial-balance_' . $this->labelForFile($payload['period']) . '.csv', $rows);
    }

    protected function csvFromPl(array $payload): StreamedResponse
    {
        $sections = $payload['data']['sections'];
        $rows = [['section', 'code', 'name_ja', 'amount']];
        foreach (['sales', 'cogs', 'sga', 'non_op_revenue', 'non_op_expense', 'special_revenue', 'special_expense'] as $sec) {
            foreach ($sections[$sec]['rows'] ?? [] as $r) {
                $rows[] = [$sec, $r['code'], $r['name_ja'], $r['balance']];
            }
            $rows[] = [$sec, '', 'subtotal', $sections[$sec]['total']];
        }
        foreach (['gross_profit', 'operating', 'ordinary', 'pretax'] as $sec) {
            $rows[] = [$sec, '', $sec, $sections[$sec]['total']];
        }
        return $this->csvDownload('pl_' . $this->labelForFile($payload['period']) . '.csv', $rows);
    }

    protected function csvFromBs(array $payload): StreamedResponse
    {
        $d = $payload['data'];
        $rows = [['side', 'group', 'code', 'name_ja', 'balance']];
        foreach (['current' => 'current_assets', 'fixed' => 'fixed_assets'] as $g => $label) {
            foreach ($d['assets'][$g]['rows'] as $r) {
                $rows[] = ['asset', $label, $r['code'], $r['name_ja'], $r['balance']];
            }
            $rows[] = ['asset', $label, '', 'subtotal', $d['assets'][$g]['total']];
        }
        $rows[] = ['asset', 'total', '', 'TOTAL ASSETS', $d['assets']['total']];

        foreach (['current' => 'current_liab', 'fixed' => 'fixed_liab'] as $g => $label) {
            foreach ($d['liabilities'][$g]['rows'] as $r) {
                $rows[] = ['liability', $label, $r['code'], $r['name_ja'], $r['balance']];
            }
            $rows[] = ['liability', $label, '', 'subtotal', $d['liabilities'][$g]['total']];
        }
        $rows[] = ['liability', 'total', '', 'TOTAL LIABILITIES', $d['liabilities']['total']];

        foreach (['capital', 'reserve'] as $g) {
            foreach ($d['equity'][$g]['rows'] as $r) {
                $rows[] = ['equity', $g, $r['code'], $r['name_ja'], $r['balance']];
            }
            $rows[] = ['equity', $g, '', 'subtotal', $d['equity'][$g]['total']];
        }
        $rows[] = ['equity', 'retained_running', '', '当期純利益(running)', $d['equity']['retained_running']];
        $rows[] = ['equity', 'total', '', 'TOTAL EQUITY', $d['equity']['total']];
        $rows[] = ['', 'total_liab_equity', '', 'TOTAL L+E', $d['total_liab_and_equity']];

        return $this->csvDownload('bs_' . $d['as_of'] . '.csv', $rows);
    }

    protected function csvFromJournalBook(array $payload): StreamedResponse
    {
        $rows = [['entry_no', 'entry_date', 'source_type', 'channel_id', 'account_code', 'account_name', 'memo', 'debit', 'credit']];
        foreach ($payload['data']['rows'] as $r) {
            $rows[] = [$r['entry_no'], $r['entry_date'], $r['source_type'], $r['channel_id'], $r['account_code'], $r['account_name'], $r['memo'], $r['debit'], $r['credit']];
        }
        $rows[] = ['', '', '', '', '', 'TOTAL', '', $payload['data']['total_debit'], $payload['data']['total_credit']];
        return $this->csvDownload('journal-book_' . $this->labelForFile($payload['period']) . '.csv', $rows);
    }

    protected function csvFromLedger(array $payload): StreamedResponse
    {
        $d = $payload['data'];
        $rows = [['account_code', 'account_name', $d['account']['code'], $d['account']['name_ja']]];
        $rows[] = ['from', $d['from'], 'to', $d['to']];
        $rows[] = ['opening_balance', '', '', $d['opening']];
        $rows[] = ['entry_no', 'entry_date', 'memo', 'debit', 'credit', 'running'];
        foreach ($d['rows'] as $r) {
            $rows[] = [$r['entry_no'], $r['entry_date'], $r['memo'], $r['debit'], $r['credit'], $r['running']];
        }
        $rows[] = ['period_total', '', '', $d['period_debit'], $d['period_credit'], ''];
        $rows[] = ['closing_balance', '', '', '', '', $d['closing']];
        return $this->csvDownload('ledger_' . $d['account']['code'] . '_' . $this->labelForFile($payload['period']) . '.csv', $rows);
    }
}
