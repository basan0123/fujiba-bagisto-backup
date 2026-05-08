<?php

namespace Fujiba\Accounting\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Fujiba\Accounting\Models\Account;
use Fujiba\Accounting\Models\AuditLog;
use Fujiba\Accounting\Models\FiscalPeriod;
use Fujiba\Accounting\Services\Reports\DashboardSummary;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardSummary $summary): View
    {
        $currentPeriod = FiscalPeriod::where('status', 'open')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first()
            ?? FiscalPeriod::where('status', 'open')->orderByDesc('start_date')->first();

        $channelId = $request->filled('channel_id') ? (int) $request->input('channel_id') : null;
        $widget = $summary->get($channelId, $request->boolean('nocache'));

        return view('accounting::admin.dashboard.index', [
            'currentPeriod' => $currentPeriod,
            'accountCount'  => Account::count(),
            'auditLogRows'  => AuditLog::count(),
            'widget'        => $widget,
        ]);
    }
}
