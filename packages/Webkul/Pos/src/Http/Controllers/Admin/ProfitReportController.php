<?php

namespace Webkul\Pos\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Profit & Loss Report
 * Aggregates:  sales revenue − COGS/discounts/taxes − waste costs − cash expense movements − applied fixed expenses
 */
class ProfitReportController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getData($request);
        }

        $outlets = DB::table('pos_outlets')->get();

        return view('pos::admin.profit-report.index', compact('outlets'));
    }

    private function getData(Request $request): JsonResponse
    {
        $month     = $request->input('month', now()->format('Y-m'));
        $outletId  = $request->input('outlet_id');

        [$year, $monthNum] = explode('-', $month);

        $from = "{$month}-01";
        $to   = date('Y-m-t', strtotime($from));

        // ── 1. Sales revenue (grand_total of completed POS orders) ──
        $salesQuery = DB::table('orders')
            ->join('pos_order', 'pos_order.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->whereIn('orders.status', ['completed', 'processing']);

        if ($outletId) {
            $salesQuery->where('pos_order.outlet_id', $outletId);
        }

        $sales = $salesQuery->select(
            DB::raw('SUM(orders.base_grand_total) as gross_sales'),
            DB::raw('SUM(orders.base_discount_amount) as total_discount'),
            DB::raw('SUM(orders.base_tax_amount) as total_tax'),
            DB::raw('SUM(orders.base_sub_total) as subtotal')
        )->first();

        // ── 2. Waste cost ──
        $wasteQuery = DB::table('pos_waste_entries')
            ->whereBetween('wasted_at', [$from, $to])
            ->whereIn('status', ['verified']);

        if ($outletId) {
            $wasteQuery->where('outlet_id', $outletId);
        }

        $wasteCost = (float) $wasteQuery->sum('total_cost');

        // ── 3. Cash movements (expense / income) ──
        $expensesQuery = DB::table('pos_cash_movements')
            ->where('type', 'expense')
            ->whereBetween('movement_date', [$from, $to]);

        $incomeQuery = DB::table('pos_cash_movements')
            ->where('type', 'income')
            ->whereBetween('movement_date', [$from, $to]);

        if ($outletId) {
            $expensesQuery->where('outlet_id', $outletId);
            $incomeQuery->where('outlet_id', $outletId);
        }

        $cashExpenses  = (float) $expensesQuery->sum('amount');
        $cashIncome    = (float) $incomeQuery->sum('amount');

        // ── 4. Fixed expense applications this month ──
        $fixedQuery = DB::table('pos_fixed_expense_applications')
            ->where('month', $month)
            ->whereIn('status', ['auto', 'manual']);

        if ($outletId) {
            $fixedQuery->where('outlet_id', $outletId);
        }

        $fixedCost = (float) $fixedQuery->sum('applied_amount');

        // ── 5. Breakdown of fixed expenses this month ──
        $fixedBreakdown = DB::table('pos_fixed_expense_applications as fa')
            ->join('pos_fixed_expenses as fe', 'fa.fixed_expense_id', '=', 'fe.id')
            ->where('fa.month', $month)
            ->whereIn('fa.status', ['auto', 'manual'])
            ->when($outletId, fn ($q) => $q->where('fa.outlet_id', $outletId))
            ->select('fe.name', 'fe.category', 'fa.applied_amount', 'fa.status')
            ->get();

        // ── Calculations ──
        $grossSales   = (float) ($sales->gross_sales ?? 0);
        $totalExpense = $wasteCost + $cashExpenses + $fixedCost;
        $netProfit    = $grossSales + $cashIncome - $totalExpense;

        return new JsonResponse([
            'month'          => $month,
            'gross_sales'    => round($grossSales, 2),
            'total_discount' => round((float) ($sales->total_discount ?? 0), 2),
            'total_tax'      => round((float) ($sales->total_tax ?? 0), 2),
            'waste_cost'     => round($wasteCost, 2),
            'cash_expenses'  => round($cashExpenses, 2),
            'cash_income'    => round($cashIncome, 2),
            'fixed_cost'     => round($fixedCost, 2),
            'total_expense'  => round($totalExpense, 2),
            'net_profit'     => round($netProfit, 2),
            'fixed_breakdown'=> $fixedBreakdown,
        ]);
    }
}
