<?php

namespace Fujiba\Accounting\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Fujiba\Accounting\Models\PayrollSlip;
use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Services\JournalPoster;
use Fujiba\Accounting\Services\Payroll\PayrollCalculator;
use Fujiba\Accounting\Services\Payroll\PayrollJournalBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PayrollController extends Controller
{
    public function index(Request $request): View
    {
        $ym = $request->input('year_month', now()->format('Y-m'));
        $slips = PayrollSlip::with('staff')
            ->where('year_month', $ym)
            ->orderBy('staff_id')
            ->get();

        return view('accounting::admin.payroll.index', [
            'slips' => $slips,
            'yearMonth' => $ym,
        ]);
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
