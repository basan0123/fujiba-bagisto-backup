<?php

namespace Webkul\Shipping\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Shipping\Models\CoolFeeSetting;

class CoolFeeController extends Controller
{
    private function defaults(): array
    {
        return [
            'cool_box_fee'   => 350,
            'frozen_box_fee' => 500,
            'cool_enabled'   => true,
            'frozen_enabled' => true,
            'fee_per_item'   => false,
            'max_fee_cap'    => 1000,
            'is_active'      => true,
        ];
    }

    public function index()
    {
        $channels  = core()->getAllChannels();
        $channelId = request('channel_id') ?: ($channels->first()?->id);
        $setting   = CoolFeeSetting::firstOrNew(
            ['channel_id' => $channelId],
            $this->defaults()
        );
        return view('shipping::admin.cool-fee.index', compact('setting', 'channels', 'channelId'));
    }

    public function edit($id)
    {
        $channels  = core()->getAllChannels();
        $channelId = (int) $id;
        $setting   = CoolFeeSetting::firstOrNew(
            ['channel_id' => $channelId],
            $this->defaults()
        );
        return view('shipping::admin.cool-fee.index', compact('setting', 'channels', 'channelId'));
    }

    public function update(Request $request, $id = null)
    {
        $data = $request->validate([
            'channel_id'     => 'nullable|integer|exists:channels,id',
            'cool_box_fee'   => 'required|numeric|min:0',
            'frozen_box_fee' => 'required|numeric|min:0',
            'cool_enabled'   => 'boolean',
            'frozen_enabled' => 'boolean',
            'fee_per_item'   => 'boolean',
            'max_fee_cap'    => 'nullable|numeric|min:0',
            'is_active'      => 'boolean',
        ]);

        $data['cool_enabled']   = $request->boolean('cool_enabled');
        $data['frozen_enabled'] = $request->boolean('frozen_enabled');
        $data['fee_per_item']   = $request->boolean('fee_per_item');
        $data['is_active']      = $request->boolean('is_active');
        $channelId              = $request->input('channel_id') ?: null;
        $data['channel_id']     = $channelId;

        CoolFeeSetting::updateOrCreate(['channel_id' => $channelId], $data);

        return redirect()->route('admin.shipping.cool-fees.index', ['channel_id' => $channelId])
            ->with('success', 'クール便設定を保存しました');
    }

    public function create()     { return $this->index(); }
    public function store()      { return $this->index(); }
    public function destroy($id) { return redirect()->back(); }
    public function show($id)    { return $this->edit($id); }
}
