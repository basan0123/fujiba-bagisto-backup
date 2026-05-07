<?php

namespace Fujiba\InventoryControl\Http\Controllers\Admin;

use Fujiba\InventoryControl\Models\InventoryBatch;
use Fujiba\InventoryControl\Services\FifoInventoryService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;

class ExpiryAlertController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $in7days = Carbon::today()->addDays(7);

        $expired = InventoryBatch::query()
            ->with('product')
            ->whereIn('status', [InventoryBatch::STATUS_ACTIVE, InventoryBatch::STATUS_EXPIRED])
            ->where('quantity_remaining', '>', 0)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', $today)
            ->orderBy('expiry_date', 'asc')
            ->get();

        $expiringSoon = InventoryBatch::query()
            ->with('product')
            ->where('status', InventoryBatch::STATUS_ACTIVE)
            ->where('quantity_remaining', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$today, $in7days])
            ->orderBy('expiry_date', 'asc')
            ->get();

        return view('inventory-control::admin.expiry-alerts.index', [
            'expired'      => $expired,
            'expiringSoon' => $expiringSoon,
            'today'        => $today,
        ]);
    }

    public function markRemoved(Request $request, InventoryBatch $batch, FifoInventoryService $fifo)
    {
        $data = $request->validate([
            'status' => 'required|in:expired,removed',
        ]);

        $fifo->markRemoved($batch, $data['status']);

        return back()->with('success', __('inventory-control::app.expiry_alerts.batch_marked_' . $data['status']));
    }
}
