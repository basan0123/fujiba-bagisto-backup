<?php

namespace Fujiba\Dashboard\Http\Controllers\Admin;

use Fujiba\Dashboard\Models\OrderShippingCost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Carrier shipping-cost import + manual edit surface.
 *
 *   GET  /admin/fujiba/dashboard/shipping-costs           landing page (upload form + recent imports)
 *   POST /admin/fujiba/dashboard/shipping-costs/import    CSV upload (tracking_number, carrier_cost[, billing_month])
 *   POST /admin/fujiba/dashboard/shipping-costs/order/{order}  manual upsert for a single order
 *
 * CSV header expected (case-insensitive, order-insensitive):
 *   tracking_number,carrier_cost[,billing_month][,carrier_code][,notes]
 *
 * Behavior:
 *   - Rows matched to orders.id via shipments.track_number (Bagisto core) or orders.id direct.
 *   - Unmatched rows are reported back to the admin (not silently dropped).
 *   - Upsert by order_id (unique). Re-imports overwrite carrier_cost; source becomes 'csv_import'.
 */
class ShippingCostImportController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $recent = OrderShippingCost::orderByDesc('updated_at')->limit(50)->get();

        $totals = DB::selectOne(
            "SELECT COUNT(*) AS rows_total,
                    COALESCE(SUM(carrier_cost), 0) AS sum_cost,
                    MAX(billing_month) AS last_month
             FROM order_shipping_costs"
        );

        return view('fujiba::admin.dashboard.shipping-cost-import', [
            'recent' => $recent,
            'totals' => $totals,
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'csv'           => ['required', 'file', 'max:5120'],
            'carrier_code'  => ['nullable', 'string', 'max:32'],
            'billing_month' => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $defaultCarrier = $request->input('carrier_code');
        $defaultMonth   = $request->input('billing_month');

        $path = $request->file('csv')->getRealPath();
        $fh   = fopen($path, 'r');
        if ($fh === false) {
            return back()->withErrors(['csv' => trans('fujiba::app.dashboard.shipping_performance.import.read_failed')]);
        }

        $headerRaw = fgetcsv($fh);
        if ($headerRaw === false) {
            fclose($fh);
            return back()->withErrors(['csv' => trans('fujiba::app.dashboard.shipping_performance.import.empty')]);
        }
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $headerRaw);

        $colTracking = array_search('tracking_number', $header, true);
        $colCost     = array_search('carrier_cost',    $header, true);
        $colMonth    = array_search('billing_month',   $header, true);
        $colCarrier  = array_search('carrier_code',    $header, true);
        $colNotes    = array_search('notes',           $header, true);
        $colOrderId  = array_search('order_id',        $header, true);

        if (($colTracking === false && $colOrderId === false) || $colCost === false) {
            fclose($fh);
            return back()->withErrors([
                'csv' => trans('fujiba::app.dashboard.shipping_performance.import.bad_header'),
            ]);
        }

        $matched  = 0;
        $unmatched = [];
        $rowNum    = 1;

        while (($row = fgetcsv($fh)) !== false) {
            $rowNum++;
            $cost = (float) ($row[$colCost] ?? 0);
            if ($cost < 0) { continue; }

            $orderId = null;
            $tracking = null;

            if ($colOrderId !== false && ! empty($row[$colOrderId])) {
                $orderId = (int) $row[$colOrderId];
            } elseif ($colTracking !== false) {
                $tracking = trim((string) ($row[$colTracking] ?? ''));
                if ($tracking !== '') {
                    $orderId = (int) (DB::table('shipments')
                        ->where('track_number', $tracking)
                        ->value('order_id') ?? 0) ?: null;
                }
            }

            if ($orderId === null) {
                $unmatched[] = ['row' => $rowNum, 'tracking' => $tracking];
                continue;
            }

            $month   = $colMonth   !== false && ! empty($row[$colMonth])   ? substr((string) $row[$colMonth], 0, 7)   : $defaultMonth;
            $carrier = $colCarrier !== false && ! empty($row[$colCarrier]) ? (string) $row[$colCarrier] : $defaultCarrier;
            $notes   = $colNotes   !== false && ! empty($row[$colNotes])   ? mb_substr((string) $row[$colNotes], 0, 255) : null;

            OrderShippingCost::updateOrCreate(
                ['order_id' => $orderId],
                [
                    'tracking_number' => $tracking,
                    'carrier_cost'    => $cost,
                    'carrier_code'    => $carrier,
                    'billing_month'   => $month,
                    'source'          => 'csv_import',
                    'notes'           => $notes,
                ],
            );
            $matched++;
        }
        fclose($fh);

        return back()->with([
            'success'           => trans('fujiba::app.dashboard.shipping_performance.import.done', ['count' => $matched]),
            'import_unmatched'  => $unmatched,
        ]);
    }

    public function upsertManual(Request $request, int $order): RedirectResponse
    {
        $data = $request->validate([
            'carrier_cost'    => ['required', 'numeric', 'min:0'],
            'tracking_number' => ['nullable', 'string', 'max:64'],
            'carrier_code'    => ['nullable', 'string', 'max:32'],
            'billing_month'   => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
            'notes'           => ['nullable', 'string', 'max:255'],
        ]);

        $exists = DB::table('orders')->where('id', $order)->exists();
        if (! $exists) {
            return back()->withErrors(['order' => trans('fujiba::app.dashboard.shipping_performance.import.unknown_order')]);
        }

        OrderShippingCost::updateOrCreate(
            ['order_id' => $order],
            array_merge($data, ['source' => 'manual']),
        );

        return back()->with('success', trans('fujiba::app.dashboard.shipping_performance.import.saved'));
    }
}
