<?php

namespace Fujiba\SagawaDelivery\Http\Controllers\Admin;

use Fujiba\SagawaDelivery\Models\ApiLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UsageController extends Controller
{
    /** @return \Illuminate\Contracts\View\View|JsonResponse */
    public function index(Request $request)
    {
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to   = $request->query('to', now()->toDateString());

        $stats = ApiLog::query()
            ->where('sandbox', false)
            ->whereBetween('created_at', [$from, $to.' 23:59:59'])
            ->selectRaw("endpoint, COUNT(*) as total, SUM(CASE WHEN result_code LIKE 'S%' THEN 1 ELSE 0 END) as success, SUM(CASE WHEN result_code NOT LIKE 'S%' OR result_code IS NULL THEN 1 ELSE 0 END) as failures")
            ->groupBy('endpoint')
            ->get();

        if ($request->wantsJson()) {
            return response()->json(['from' => $from, 'to' => $to, 'stats' => $stats]);
        }

        return view('sagawa::admin.usage', compact('stats', 'from', 'to'));
    }
}
