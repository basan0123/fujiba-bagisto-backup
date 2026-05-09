<?php

namespace Fujiba\Accounting\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Fujiba\Accounting\Models\PayrollSlip;
use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Services\JournalPoster;
use Fujiba\Accounting\Services\Payroll\PayrollCalculator;
use Fujiba\Accounting\Services\Payroll\PayrollJournalBuilder;
use Fujiba\Accounting\Services\Payroll\ZenginExportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $ym = $request->input('year_month', now()->format('Y-m'));
        $slips = PayrollSlip::with('staff')
            ->where('year_month', $ym)
            ->orderBy('staff_id')
            ->get();

        // Sprint 6 Phase 2A: bulk PDF zip
        if ($request->input('format') === 'pdf-zip') {
            return $this->payslipZip($slips, $ym);
        }
        // Sprint 6 Phase 2B: Zengin format export
        if ($request->input('format') === 'zengin') {
            return $this->zengin($slips, $ym, $request);
        }

        return view('accounting::admin.payroll.index', [
            'slips' => $slips,
            'yearMonth' => $ym,
        ]);
    }

    public function pdf(int $id): Response
    {
        $slip = PayrollSlip::with('staff')->findOrFail($id);
        $pdf = Pdf::loadView('accounting::admin.payroll.pdf.slip', [
            'slip'        => $slip,
            'companyName' => config('app.name'),
            'generatedAt' => now(),
        ])->setPaper('A4', 'portrait')->set_option('defaultFont', 'DejaVu Sans');

        $name = sprintf('payslip_%s_%s.pdf', $slip->year_month, $slip->staff?->staff_code ?? $slip->staff_id);
        return $pdf->download($name);
    }

    protected function payslipZip($slips, string $ym): StreamedResponse
    {
        $tmp = tempnam(sys_get_temp_dir(), 'payslips_') . '.zip';
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $totalGross = 0;
        $totalNet = 0;
        foreach ($slips as $slip) {
            $pdf = Pdf::loadView('accounting::admin.payroll.pdf.slip', [
                'slip'        => $slip,
                'companyName' => config('app.name'),
                'generatedAt' => now(),
            ])->setPaper('A4', 'portrait')->set_option('defaultFont', 'DejaVu Sans');
            $name = sprintf('payslip_%s_%s.pdf', $slip->year_month, $slip->staff?->staff_code ?? $slip->staff_id);
            $zip->addFromString($name, $pdf->output());
            $totalGross += (int) $slip->gross_pay;
            $totalNet   += (int) $slip->net_pay;
        }

        // Manifest with totals so the bookkeeper has a quick crosscheck
        $zip->addFromString('MANIFEST.txt', "Fujiba payslips bundle\nperiod={$ym}\ncount=" . $slips->count() .
            "\ntotal_gross={$totalGross}\ntotal_net={$totalNet}\ngenerated=" . now()->toDateTimeString() . "\n");

        $zip->close();

        return response()->streamDownload(function () use ($tmp) {
            readfile($tmp);
            @unlink($tmp);
        }, "payslips_{$ym}.zip", ['Content-Type' => 'application/zip']);
    }

    protected function zengin($slips, string $ym, Request $request): StreamedResponse
    {
        $svc = app(ZenginExportService::class);
        $sender = [
            'sender_code'   => (string) ($request->input('sender_code') ?? config('accounting.zengin.sender_code', '0000000000')),
            'sender_name'   => (string) ($request->input('sender_name') ?? config('accounting.zengin.sender_name_kana', strtoupper(config('app.name', 'FUJIBA')))),
            'transfer_date' => $request->input('transfer_date') ?: Carbon::parse("{$ym}-25")->format('md'),
            'bank_code'     => (string) ($request->input('payer_bank_code')   ?? config('accounting.zengin.payer_bank_code', '0000')),
            'bank_name'     => (string) ($request->input('payer_bank_name')   ?? config('accounting.zengin.payer_bank_name_kana', '')),
            'branch_code'   => (string) ($request->input('payer_branch_code') ?? config('accounting.zengin.payer_branch_code', '000')),
            'branch_name'   => (string) ($request->input('payer_branch_name') ?? config('accounting.zengin.payer_branch_name_kana', '')),
            'account_type'  => (string) ($request->input('payer_account_type') ?? config('accounting.zengin.payer_account_type', '1')),
            'account_no'    => (string) ($request->input('payer_account_no')   ?? config('accounting.zengin.payer_account_no', '0000000')),
        ];

        $payload = $svc->build($slips, $sender);

        return response()->streamDownload(function () use ($payload) {
            echo $payload['data'];
        }, "zengin_{$ym}.dat", ['Content-Type' => 'text/plain; charset=Shift_JIS']);
    }

    public function run(Request $request, PayrollCalculator $calc): RedirectResponse
    {
        $ym = $request->validate(['year_month' => 'required|regex:/^\d{4}-\d{2}$/'])['year_month'];
        $payDate = $request->filled('pay_date') ? Carbon::parse($request->input('pay_date')) : null;

        $count = 0;
        foreach (Staff::where('is_active', true)->get() as $s) {
            $calc->calculate($s, $ym, $payDate);
            $count++;
        }

        return redirect()->route('admin.accounting.payroll.index', ['year_month' => $ym])
            ->with('success', "{$count} 名分の給与を計算しました。");
    }

    public function show(int $id): View
    {
        return view('accounting::admin.payroll.show', [
            'slip' => PayrollSlip::with('staff', 'journalEntry')->findOrFail($id),
        ]);
    }

    public function finalize(int $id, PayrollJournalBuilder $builder, JournalPoster $poster): RedirectResponse
    {
        $slip = PayrollSlip::findOrFail($id);
        if ($slip->status === 'finalized' || $slip->status === 'paid') {
            return back()->with('error', '既に確定済みです。');
        }
        if ((float) $slip->gross_pay <= 0) {
            return back()->with('error', '支給合計が0のため確定できません。');
        }

        $payload = $builder->build($slip);
        $entry = $poster->post($payload);

        $slip->update([
            'journal_entry_id' => $entry->id,
            'status'           => 'finalized',
            'finalized_at'     => now(),
        ]);

        return back()->with('success', "仕訳 {$entry->entry_no} を作成しました。");
    }
}
