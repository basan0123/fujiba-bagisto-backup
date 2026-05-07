<?php

namespace Fujiba\Accounting\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Fujiba\Accounting\Models\Account;
use Fujiba\Accounting\Models\AuditLog;
use Fujiba\Accounting\Models\FiscalPeriod;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $currentPeriod = FiscalPeriod::where('status', 'open')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first()
            ?? FiscalPeriod::where('status', 'open')->orderByDesc('start_date')->first();

        return view('accounting::admin.dashboard.index', [
            'currentPeriod' => $currentPeriod,
            'accountCount'  => Account::count(),
            'auditLogRows'  => AuditLog::count(),
        ]);
    }
}
