<?php

namespace Webkul\Shipping\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Shipping\Models\ShippingZone;
use Webkul\Shipping\Models\ShippingZonePrefecture;
use Webkul\Shipping\Models\ShippingZoneRate;

class ZoneShippingController extends Controller
{
    // All 47 prefectures grouped by region for the UI
    public const PREFECTURE_MAP = [
        '01' => '北海道',
        '02' => '青森県', '03' => '岩手県', '04' => '宮城県', '05' => '秋田県', '06' => '山形県', '07' => '福島県',
        '08' => '茨城県', '09' => '栃木県', '10' => '群馬県', '11' => '埼玉県', '12' => '千葉県', '13' => '東京都', '14' => '神奈川県',
        '15' => '新潟県', '16' => '富山県', '17' => '石川県', '18' => '福井県', '19' => '山梨県', '20' => '長野県',
        '21' => '岐阜県', '22' => '静岡県', '23' => '愛知県', '24' => '三重県',
        '25' => '滋賀県', '26' => '京都府', '27' => '大阪府', '28' => '兵庫県', '29' => '奈良県', '30' => '和歌山県',
        '31' => '鳥取県', '32' => '島根県', '33' => '岡山県', '34' => '広島県', '35' => '山口県',
        '36' => '徳島県', '37' => '香川県', '38' => '愛媛県', '39' => '高知県',
        '40' => '福岡県', '41' => '佐賀県', '42' => '長崎県', '43' => '熊本県', '44' => '大分県', '45' => '宮崎県', '46' => '鹿児島県',
        '47' => '沖縄県',
    ];

    // Regional groupings for the checkbox UI
    public const REGION_GROUPS = [
        '北海道'   => ['01'],
        '東北'     => ['02', '03', '04', '05', '06', '07'],
        '関東'     => ['08', '09', '10', '11', '12', '13', '14', '19'],
        '甲信越・北陸' => ['15', '16', '17', '18', '20'],
        '東海'     => ['21', '22', '23', '24'],
        '近畿'     => ['25', '26', '27', '28', '29', '30'],
        '中国'     => ['31', '32', '33', '34', '35'],
        '四国'     => ['36', '37', '38', '39'],
        '九州'     => ['40', '41', '42', '43', '44', '45', '46'],
        '沖縄'     => ['47'],
    ];

    // Kimono store pre-defined zone seed data (store_id=10 rules)
    private const KIMONO_ZONES = [
        [
            'zone_name'    => '関東・中部・東北南部',
            'zone_code'    => 'zone_a',
            'sort_order'   => 1,
            'prefectures'  => ['04','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24'],
            'rates'        => [
                ['qty_from'=>1,  'qty_to'=>1,  'shipping_price'=>430],
                ['qty_from'=>2,  'qty_to'=>3,  'shipping_price'=>600],
                ['qty_from'=>4,  'qty_to'=>5,  'shipping_price'=>840],
                ['qty_from'=>6,  'qty_to'=>15, 'shipping_price'=>1130],
                ['qty_from'=>16, 'qty_to'=>30, 'shipping_price'=>1430],
            ],
        ],
        [
            'zone_name'    => '東北北部・近畿',
            'zone_code'    => 'zone_b',
            'sort_order'   => 2,
            'prefectures'  => ['02','03','05','25','26','27','28','29','30'],
            'rates'        => [
                ['qty_from'=>1,  'qty_to'=>1,  'shipping_price'=>430],
                ['qty_from'=>2,  'qty_to'=>3,  'shipping_price'=>600],
                ['qty_from'=>4,  'qty_to'=>5,  'shipping_price'=>690],
                ['qty_from'=>6,  'qty_to'=>15, 'shipping_price'=>1250],
                ['qty_from'=>16, 'qty_to'=>30, 'shipping_price'=>1550],
            ],
        ],
        [
            'zone_name'    => '中国・四国',
            'zone_code'    => 'zone_c',
            'sort_order'   => 3,
            'prefectures'  => ['31','32','33','34','35','36','37','38','39'],
            'rates'        => [
                ['qty_from'=>1,  'qty_to'=>1,  'shipping_price'=>430],
                ['qty_from'=>2,  'qty_to'=>3,  'shipping_price'=>600],
                ['qty_from'=>4,  'qty_to'=>5,  'shipping_price'=>1090],
                ['qty_from'=>6,  'qty_to'=>15, 'shipping_price'=>1380],
                ['qty_from'=>16, 'qty_to'=>30, 'shipping_price'=>1690],
            ],
        ],
        [
            'zone_name'    => '北海道・九州',
            'zone_code'    => 'zone_d',
            'sort_order'   => 4,
            'prefectures'  => ['01','40','41','42','43','44','45','46'],
            'rates'        => [
                ['qty_from'=>1,  'qty_to'=>1,  'shipping_price'=>430],
                ['qty_from'=>2,  'qty_to'=>3,  'shipping_price'=>600],
                ['qty_from'=>4,  'qty_to'=>5,  'shipping_price'=>1360],
                ['qty_from'=>6,  'qty_to'=>15, 'shipping_price'=>1640],
                ['qty_from'=>16, 'qty_to'=>30, 'shipping_price'=>1950],
            ],
        ],
        [
            'zone_name'    => '沖縄',
            'zone_code'    => 'zone_e',
            'sort_order'   => 5,
            'prefectures'  => ['47'],
            'rates'        => [
                ['qty_from'=>1,  'qty_to'=>1,  'shipping_price'=>430],
                ['qty_from'=>2,  'qty_to'=>3,  'shipping_price'=>600],
                ['qty_from'=>4,  'qty_to'=>5,  'shipping_price'=>1360],
                ['qty_from'=>6,  'qty_to'=>15, 'shipping_price'=>1970],
                ['qty_from'=>16, 'qty_to'=>30, 'shipping_price'=>2610],
            ],
        ],
    ];

    public function index(Request $request)
    {
        $channels  = core()->getAllChannels();
        $channelId = $request->query('channel_id') ? (int)$request->query('channel_id') : null;

        $query = ShippingZone::with(['prefectures', 'rates'])->orderBy('sort_order');
        if ($channelId) {
            $query->where('channel_id', $channelId);
        }
        $zones = $query->get();

        return view('shipping::admin.zones.index', [
            'zones'        => $zones,
            'channels'     => $channels,
            'channelId'    => $channelId,
            'prefectureMap'=> self::PREFECTURE_MAP,
        ]);
    }

    public function create(Request $request)
    {
        $channels  = core()->getAllChannels();
        $channelId = $request->query('channel_id') ? (int)$request->query('channel_id') : null;

        return view('shipping::admin.zones.form', [
            'zone'          => null,
            'channels'      => $channels,
            'channelId'     => $channelId,
            'prefectureMap' => self::PREFECTURE_MAP,
            'regionGroups'  => self::REGION_GROUPS,
            'selectedPrefs' => [],
            'rates'         => [],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'zone_name'          => 'required|string|max:100',
            'zone_code'          => 'nullable|string|max:50',
            'channel_id'         => 'nullable|integer',
            'sort_order'         => 'nullable|integer',
            'is_active'          => 'nullable|boolean',
            'prefectures'        => 'required|array|min:1',
            'prefectures.*'      => 'string|max:10',
            'rates'              => 'required|array|min:1',
            'rates.*.qty_from'   => 'required|integer|min:0',
            'rates.*.qty_to'     => 'required|integer|min:1',
            'rates.*.price'      => 'required|numeric|min:0',
        ]);

        $channelId = $request->input('channel_id') ?: null;

        $zone = ShippingZone::create([
            'channel_id'  => $channelId,
            'zone_name'   => $data['zone_name'],
            'zone_code'   => $data['zone_code'] ?? null,
            'sort_order'  => $data['sort_order'] ?? 0,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        $this->syncPrefectures($zone, $data['prefectures']);
        $this->syncRates($zone, $data['rates']);

        return redirect()
            ->route('admin.shipping.zones.index', array_filter(['channel_id' => $channelId]))
            ->with('success', 'ゾーン「' . $zone->zone_name . '」を作成しました');
    }

    public function edit($id)
    {
        $zone = ShippingZone::with(['prefectures', 'rates'])->findOrFail($id);
        $channels = core()->getAllChannels();

        return view('shipping::admin.zones.form', [
            'zone'          => $zone,
            'channels'      => $channels,
            'channelId'     => $zone->channel_id,
            'prefectureMap' => self::PREFECTURE_MAP,
            'regionGroups'  => self::REGION_GROUPS,
            'selectedPrefs' => $zone->prefectures->pluck('prefecture_code')->toArray(),
            'rates'         => $zone->rates->toArray(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $zone = ShippingZone::findOrFail($id);

        $data = $request->validate([
            'zone_name'          => 'required|string|max:100',
            'zone_code'          => 'nullable|string|max:50',
            'channel_id'         => 'nullable|integer',
            'sort_order'         => 'nullable|integer',
            'is_active'          => 'nullable|boolean',
            'prefectures'        => 'required|array|min:1',
            'prefectures.*'      => 'string|max:10',
            'rates'              => 'required|array|min:1',
            'rates.*.qty_from'   => 'required|integer|min:0',
            'rates.*.qty_to'     => 'required|integer|min:1',
            'rates.*.price'      => 'required|numeric|min:0',
        ]);

        $channelId = $request->input('channel_id') ?: null;

        $zone->update([
            'channel_id'  => $channelId,
            'zone_name'   => $data['zone_name'],
            'zone_code'   => $data['zone_code'] ?? null,
            'sort_order'  => $data['sort_order'] ?? 0,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        $this->syncPrefectures($zone, $data['prefectures']);
        $this->syncRates($zone, $data['rates']);

        return redirect()
            ->route('admin.shipping.zones.index', array_filter(['channel_id' => $channelId]))
            ->with('success', 'ゾーン「' . $zone->zone_name . '」を更新しました');
    }

    public function destroy($id)
    {
        $zone = ShippingZone::findOrFail($id);
        $name = $zone->zone_name;
        $channelId = $zone->channel_id;
        $zone->delete(); // cascades prefectures and rates

        return redirect()
            ->route('admin.shipping.zones.index', array_filter(['channel_id' => $channelId]))
            ->with('success', 'ゾーン「' . $name . '」を削除しました');
    }

    /**
     * Seed pre-defined Kimono store zones (channel 1 = Kimono Store)
     */
    public function seed(Request $request)
    {
        $channelId = $request->input('channel_id') ?: 1;

        foreach (self::KIMONO_ZONES as $i => $zoneData) {
            $zone = ShippingZone::updateOrCreate(
                ['channel_id' => $channelId, 'zone_name' => $zoneData['zone_name']],
                [
                    'zone_code'  => $zoneData['zone_code'],
                    'sort_order' => $zoneData['sort_order'],
                    'is_active'  => true,
                ]
            );

            $this->syncPrefectures($zone, $zoneData['prefectures']);

            // Convert seed rates format (price key) to controller format
            $rates = array_map(fn($r) => [
                'qty_from' => $r['qty_from'],
                'qty_to'   => $r['qty_to'],
                'price'    => $r['shipping_price'],
            ], $zoneData['rates']);

            $this->syncRates($zone, $rates);
        }

        return redirect()
            ->route('admin.shipping.zones.index', ['channel_id' => $channelId])
            ->with('success', '着物店のゾーン送料（5ゾーン）をシードしました');
    }

    // ─────────────────────────────────────────────────────────
    //  Private helpers
    // ─────────────────────────────────────────────────────────

    private function syncPrefectures(ShippingZone $zone, array $codes): void
    {
        $zone->prefectures()->delete();

        $rows = [];
        foreach ($codes as $code) {
            $name = self::PREFECTURE_MAP[$code] ?? $code;
            $rows[] = ['zone_id' => $zone->id, 'prefecture_code' => $code, 'prefecture_name_ja' => $name];
        }

        ShippingZonePrefecture::insert($rows);
    }

    private function syncRates(ShippingZone $zone, array $rates): void
    {
        $zone->rates()->delete();

        $rows = [];
        foreach ($rates as $r) {
            if ($r['qty_from'] === '' || $r['qty_to'] === '' || $r['price'] === '') continue;
            $rows[] = [
                'zone_id'        => $zone->id,
                'qty_from'       => (int)$r['qty_from'],
                'qty_to'         => (int)$r['qty_to'],
                'shipping_price' => (float)$r['price'],
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }

        ShippingZoneRate::insert($rows);
    }
}
