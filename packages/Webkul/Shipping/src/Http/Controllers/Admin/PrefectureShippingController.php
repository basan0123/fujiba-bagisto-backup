<?php

namespace Webkul\Shipping\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Shipping\Models\PrefectureShippingRate;

class PrefectureShippingController extends Controller
{
    private array $prefectures = [
        '01'=>'北海道','02'=>'青森県','03'=>'岩手県','04'=>'宮城県','05'=>'秋田県',
        '06'=>'山形県','07'=>'福島県','08'=>'茨城県','09'=>'栃木県','10'=>'群馬県',
        '11'=>'埼玉県','12'=>'千葉県','13'=>'東京都','14'=>'神奈川県','15'=>'新潟県',
        '16'=>'富山県','17'=>'石川県','18'=>'福井県','19'=>'山梨県','20'=>'長野県',
        '21'=>'岐阜県','22'=>'静岡県','23'=>'愛知県','24'=>'三重県','25'=>'滋賀県',
        '26'=>'京都府','27'=>'大阪府','28'=>'兵庫県','29'=>'奈良県','30'=>'和歌山県',
        '31'=>'鳥取県','32'=>'島根県','33'=>'岡山県','34'=>'広島県','35'=>'山口県',
        '36'=>'徳島県','37'=>'香川県','38'=>'愛媛県','39'=>'高知県','40'=>'福岡県',
        '41'=>'佐賀県','42'=>'長崎県','43'=>'熊本県','44'=>'大分県','45'=>'宮崎県',
        '46'=>'鹿児島県','47'=>'沖縄県',
    ];

    public function index()
    {
        $channels  = core()->getAllChannels();
        $channelId = request('channel_id') ?: null;

        $query = PrefectureShippingRate::orderBy('prefecture_code')->orderBy('qty_from');
        if ($channelId) {
            $query->where('channel_id', $channelId);
        }
        $rates = $query->get()->groupBy('prefecture_code');

        return view('shipping::admin.prefecture.index', [
            'rates'       => $rates,
            'prefectures' => $this->prefectures,
            'channels'    => $channels,
            'channelId'   => $channelId,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'channel_id'      => 'nullable|integer|exists:channels,id',
            'prefecture_code' => 'required|string|max:10',
            'qty_from'        => 'required|integer|min:0',
            'qty_to'          => 'required|integer|min:1',
            'shipping_price'  => 'required|numeric|min:0',
            'is_active'       => 'boolean',
        ]);

        $data['prefecture_name_ja'] = $this->prefectures[$data['prefecture_code']] ?? $data['prefecture_code'];
        $data['prefecture_name_en'] = $data['prefecture_code'];
        $data['is_active']          = $request->boolean('is_active', true);
        $data['channel_id']         = $request->input('channel_id') ?: null;

        PrefectureShippingRate::updateOrCreate(
            [
                'prefecture_code' => $data['prefecture_code'],
                'qty_from'        => $data['qty_from'],
                'qty_to'          => $data['qty_to'],
                'channel_id'      => $data['channel_id'],
            ],
            $data
        );

        return redirect()->route('admin.shipping.prefecture-rates.index', ['channel_id' => $data['channel_id']])
            ->with('success', '都道府県送料を保存しました');
    }

    public function destroy($id)
    {
        PrefectureShippingRate::findOrFail($id)->delete();
        return redirect()->route('admin.shipping.prefecture-rates.index')
            ->with('success', '削除しました');
    }

    public function update(Request $request, $id)   { return $this->store($request); }
    public function create()                         { return $this->index(); }
    public function edit($id)                        { return $this->index(); }
    public function show($id)                        { return $this->index(); }
    public function import(Request $request)         { return redirect()->back(); }
    public function export()                         { return response()->json([]); }
}
