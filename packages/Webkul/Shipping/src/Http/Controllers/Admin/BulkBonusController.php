<?php

namespace Webkul\Shipping\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Shipping\Models\BulkBonusRule;

class BulkBonusController extends Controller
{
    public function index()
    {
        $channels  = core()->getAllChannels();
        $channelId = request('channel_id') ?: null;

        $query = BulkBonusRule::orderBy('buy_qty');
        if ($channelId) {
            $query->where('channel_id', $channelId);
        }
        $rules = $query->get();
        return view('shipping::admin.bulk-bonus.index', compact('rules', 'channels', 'channelId'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'channel_id'           => 'nullable|integer|exists:channels,id',
            'buy_qty'              => 'required|integer|min:1',
            'free_qty'             => 'nullable|integer|min:0',
            'discount_type'        => 'nullable|in:free_items,percent_off_order,fixed_per_tier',
            'percent_off'          => 'nullable|numeric|min:0|max:100',
            'fixed_amount'         => 'nullable|numeric|min:0',
            'min_product_price'    => 'nullable|numeric|min:0',
            'max_free_per_order'   => 'nullable|integer|min:1',
            'priority'             => 'nullable|integer',
            'stackable'            => 'boolean',
            'replaces_lower_tiers' => 'boolean',
            'block_coupons'        => 'boolean',
            'is_active'            => 'boolean',
        ]);

        $data['discount_type']        = $request->input('discount_type', 'free_items');
        $data['stackable']            = $request->boolean('stackable', true);
        $data['replaces_lower_tiers'] = $request->boolean('replaces_lower_tiers', false);
        $data['block_coupons']        = $request->boolean('block_coupons', false);
        $data['is_active']            = $request->boolean('is_active', true);
        $data['channel_id']           = $request->input('channel_id') ?: null;
        $data['priority']             = (int) $request->input('priority', 0);

        // Sanity defaults: percent and fixed-per-tier rules don't need
        // free_qty; free-items rules don't need percent_off / fixed_amount.
        // Each unused field is normalised to NULL or 0 as appropriate.
        if (in_array($data['discount_type'], ['percent_off_order', 'fixed_per_tier'], true)) {
            $data['free_qty'] = 0;
        } elseif (! isset($data['free_qty']) || $data['free_qty'] === null) {
            $data['free_qty'] = 1;
        }
        if (empty($data['percent_off'])) {
            $data['percent_off'] = null;
        }
        if (empty($data['fixed_amount'])) {
            $data['fixed_amount'] = null;
        }
        if (empty($data['min_product_price'])) {
            $data['min_product_price'] = null;
        }

        // Hard guard: a fixed_per_tier rule without a fixed_amount is a
        // misconfiguration that would silently no-op. Reject up front.
        if ($data['discount_type'] === 'fixed_per_tier' && ! $data['fixed_amount']) {
            return back()->withErrors([
                'fixed_amount' => '固定割引額 (fixed_amount) を入力してください',
            ])->withInput();
        }

        BulkBonusRule::create($data);
        return redirect()->route('admin.shipping.bulk-bonus.index', ['channel_id' => $data['channel_id']])
            ->with('success', 'まとめ買いボーナスを追加しました');
    }

    public function destroy($id)
    {
        BulkBonusRule::findOrFail($id)->delete();
        return redirect()->route('admin.shipping.bulk-bonus.index')
            ->with('success', '削除しました');
    }

    public function create() { return $this->index(); }
    public function edit($id) { return $this->index(); }
    public function update(Request $request, $id) { return $this->store($request); }
    public function show($id) { return $this->index(); }
}
