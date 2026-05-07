<?php

namespace Webkul\Shipping\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Shipping\Models\FreeShippingThreshold;

class FreeShippingThresholdController extends Controller
{
    public function index()
    {
        $channels   = core()->getAllChannels();
        $thresholds = FreeShippingThreshold::orderBy('channel_id')->orderBy('threshold_amount')->get();
        return view('shipping::admin.thresholds.index', compact('thresholds', 'channels'));
    }

    public function create()
    {
        $channels = core()->getAllChannels();
        return view('shipping::admin.thresholds.form', ['threshold' => new FreeShippingThreshold(), 'channels' => $channels]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'channel_id'        => 'nullable|integer|exists:channels,id',
            'threshold_amount'  => 'required|numeric|min:0',
            'label'             => 'required|string|max:255',
            'apply_to_cool'     => 'boolean',
            'apply_to_frozen'   => 'boolean',
            'is_active'         => 'boolean',
            'prefecture_codes'  => 'nullable|array',
            'prefecture_codes.*' => 'string|size:2',
            'base_shipping_fee' => 'nullable|numeric|min:0',
        ]);
        $data['apply_to_cool']    = $request->boolean('apply_to_cool');
        $data['apply_to_frozen']  = $request->boolean('apply_to_frozen');
        $data['is_active']        = $request->boolean('is_active');
        $data['channel_id']       = $request->input('channel_id') ?: null;
        $data['prefecture_codes'] = array_filter($request->input('prefecture_codes', [])) ?: null;
        $data['base_shipping_fee'] = (float) ($request->input('base_shipping_fee') ?: 0);

        FreeShippingThreshold::create($data);
        return redirect()->route('admin.shipping.thresholds.index')
            ->with('success', '無料配送しきい値を追加しました');
    }

    public function edit($id)
    {
        $threshold = FreeShippingThreshold::findOrFail($id);
        $channels  = core()->getAllChannels();
        return view('shipping::admin.thresholds.form', compact('threshold', 'channels'));
    }

    public function update(Request $request, $id)
    {
        $threshold = FreeShippingThreshold::findOrFail($id);
        $data = $request->validate([
            'channel_id'        => 'nullable|integer|exists:channels,id',
            'threshold_amount'  => 'required|numeric|min:0',
            'label'             => 'required|string|max:255',
            'apply_to_cool'     => 'boolean',
            'apply_to_frozen'   => 'boolean',
            'is_active'         => 'boolean',
            'prefecture_codes'  => 'nullable|array',
            'prefecture_codes.*' => 'string|size:2',
            'base_shipping_fee' => 'nullable|numeric|min:0',
        ]);
        $data['apply_to_cool']    = $request->boolean('apply_to_cool');
        $data['apply_to_frozen']  = $request->boolean('apply_to_frozen');
        $data['is_active']        = $request->boolean('is_active');
        $data['channel_id']       = $request->input('channel_id') ?: null;
        $data['prefecture_codes'] = array_filter($request->input('prefecture_codes', [])) ?: null;
        $data['base_shipping_fee'] = (float) ($request->input('base_shipping_fee') ?: 0);

        $threshold->update($data);
        return redirect()->route('admin.shipping.thresholds.index')
            ->with('success', '更新しました');
    }

    public function destroy($id)
    {
        FreeShippingThreshold::findOrFail($id)->delete();
        return redirect()->route('admin.shipping.thresholds.index')
            ->with('success', '削除しました');
    }

    public function show($id) { return $this->edit($id); }
}
