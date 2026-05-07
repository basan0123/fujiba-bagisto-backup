<?php

namespace Fujiba\InventoryControl\Http\Controllers\Admin;

use Fujiba\InventoryControl\Models\InventoryBatch;
use Fujiba\InventoryControl\Services\FifoInventoryService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Product\Models\Product;

class BatchController extends Controller
{
    public function index()
    {
        $batches = InventoryBatch::query()
            ->with('product', 'inventorySource', 'channel')
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('inventory-control::admin.batches.index', compact('batches'));
    }

    public function create()
    {
        $channels = core()->getAllChannels();
        $inventorySources = InventorySource::where('status', 1)->orderBy('priority')->get();

        return view('inventory-control::admin.batches.create', compact('channels', 'inventorySources'));
    }

    public function store(Request $request, FifoInventoryService $fifo)
    {
        $data = $request->validate([
            'product_id'          => 'required|integer|exists:products,id',
            'channel_id'          => 'nullable|integer',
            'inventory_source_id' => 'nullable|integer|exists:inventory_sources,id',
            'batch_number'        => 'nullable|string|max:100',
            'quantity'            => 'required|integer|min:1',
            'cost_price'          => 'nullable|numeric|min:0',
            'manufacture_date'    => 'nullable|date',
            'expiry_date'         => 'nullable|date|after_or_equal:today',
        ]);

        $product = Product::findOrFail($data['product_id']);

        if ($product->has_expiry_date && empty($data['expiry_date'])) {
            return back()
                ->withInput()
                ->withErrors(['expiry_date' => __('inventory-control::app.batches.expiry_required')]);
        }

        $fifo->receive($data);

        return redirect()
            ->route('admin.inventory_control.batches.index')
            ->with('success', __('inventory-control::app.batches.created'));
    }
}
