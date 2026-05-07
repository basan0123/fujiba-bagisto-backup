<?php

namespace Fujiba\Accounting\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Fujiba\Accounting\Models\JournalEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    public function index(Request $request): View
    {
        $query = JournalEntry::query()
            ->with('lines.account')
            ->orderByDesc('entry_date')
            ->orderByDesc('id');

        if ($request->filled('from')) {
            $query->whereDate('entry_date', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('entry_date', '<=', $request->date('to'));
        }
        if ($request->filled('source_type')) {
            $query->where('source_type', $request->string('source_type'));
        }
        if ($request->filled('channel_id')) {
            $query->where('channel_id', $request->integer('channel_id'));
        }

        $entries = $query->paginate(50)->withQueryString();

        $sourceTypes = JournalEntry::query()
            ->select('source_type')
            ->distinct()
            ->orderBy('source_type')
            ->pluck('source_type');

        return view('accounting::admin.journal.index', [
            'entries'     => $entries,
            'sourceTypes' => $sourceTypes,
            'filters'     => [
                'from'        => $request->input('from'),
                'to'          => $request->input('to'),
                'source_type' => $request->input('source_type'),
                'channel_id'  => $request->input('channel_id'),
            ],
        ]);
    }

    public function show(int $id): View
    {
        $entry = JournalEntry::with(['lines.account', 'fiscalPeriod'])->findOrFail($id);
        return view('accounting::admin.journal.show', ['entry' => $entry]);
    }
}
