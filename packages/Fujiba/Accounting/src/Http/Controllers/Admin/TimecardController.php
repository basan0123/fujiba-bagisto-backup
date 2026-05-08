<?php

namespace Fujiba\Accounting\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Models\Timecard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class TimecardController extends Controller
{
    public function index(Request $request): View
    {
        $query = Timecard::query()->with('staff')->orderByDesc('work_date')->orderBy('staff_id');

        if ($request->filled('from')) {
            $query->whereDate('work_date', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('work_date', '<=', $request->date('to'));
        }
        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->integer('staff_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('accounting::admin.timecards.index', [
            'cards'   => $query->paginate(50)->withQueryString(),
            'filters' => $request->only(['from', 'to', 'staff_id', 'status']),
            'staff'   => Staff::where('is_active', true)->orderBy('staff_code')->get(['id', 'staff_code', 'name_ja']),
        ]);
    }

    public function edit(int $id): View
    {
        return view('accounting::admin.timecards.edit', [
            'card' => Timecard::with('staff')->findOrFail($id),
        ]);
    }

    public function update(int $id, Request $request): RedirectResponse
    {
        $card = Timecard::findOrFail($id);
        if ($card->status === 'approved') {
            return back()->with('error', '承認済みのタイムカードは編集できません。');
        }

        $data = $request->validate([
            'clock_in'        => ['nullable', 'date'],
            'clock_out'       => ['nullable', 'date'],
            'break_minutes'   => ['nullable', 'integer', 'min:0', 'max:1440'],
            'work_minutes'    => ['nullable', 'integer', 'min:0'],
            'overtime_minutes'=> ['nullable', 'integer', 'min:0'],
            'night_minutes'   => ['nullable', 'integer', 'min:0'],
            'holiday_minutes' => ['nullable', 'integer', 'min:0'],
            'status'          => ['nullable', Rule::in(['open', 'closed', 'edited', 'approved'])],
            'notes'           => ['nullable', 'string'],
        ]);

        // Recompute work_minutes from clock_in/clock_out if both present and not overridden by operator
        if (! $request->filled('work_minutes') && $data['clock_in'] && $data['clock_out']) {
            $in = Carbon::parse($data['clock_in']);
            $out = Carbon::parse($data['clock_out']);
            $break = (int) ($data['break_minutes'] ?? 0);
            $data['work_minutes'] = max(0, $in->diffInMinutes($out) - $break);
        }

        $card->fill($data);
        if ($card->status === 'open' && ! empty($data['clock_out'])) {
            $card->status = 'edited';
        }
        $card->save();

        return back()->with('success', '更新しました。');
    }

    public function approve(int $id): RedirectResponse
    {
        $card = Timecard::findOrFail($id);
        if ($card->status === 'approved') {
            return back()->with('error', '既に承認済みです。');
        }
        if (empty($card->clock_out)) {
            return back()->with('error', '退勤時刻が未入力のため承認できません。');
        }
        $card->update([
            'status'      => 'approved',
            'approved_by' => auth('admin')->id() ?? auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', '承認しました。');
    }
}
