<?php

namespace Webkul\Shipping\Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Shipping\Models\BulkBonusRule;
use Webkul\Shipping\Models\CoolFeeSetting;
use Webkul\Shipping\Models\FixedShippingFee;
use Webkul\Shipping\Models\FreeShippingThreshold;
use Webkul\Shipping\Models\PrefectureShippingRate;

class ShippingRuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCoolFee();
        $this->seedFreeThresholds();
        $this->seedFixedFee();
        $this->seedBulkBonus();
        $this->seedPrefectureRates();
    }

    private function seedCoolFee(): void
    {
        CoolFeeSetting::firstOrCreate(
            ['channel_id' => null],
            [
                'cool_box_fee'   => 350,
                'frozen_box_fee' => 500,
                'cool_enabled'   => true,
                'frozen_enabled' => true,
                'fee_per_item'   => false,
                'max_fee_cap'    => 1000,
                'priority'       => 0,
                'is_active'      => true,
            ]
        );
    }

    private function seedFreeThresholds(): void
    {
        FreeShippingThreshold::firstOrCreate(
            ['channel_id' => null, 'threshold_amount' => 5000],
            [
                'label'          => '5,000円以上送料無料',
                'apply_to_cool'  => false,
                'apply_to_frozen'=> false,
                'priority'       => 1,
                'is_active'      => true,
            ]
        );

        FreeShippingThreshold::firstOrCreate(
            ['channel_id' => null, 'threshold_amount' => 8000],
            [
                'label'          => '8,000円以上クール便無料',
                'apply_to_cool'  => true,
                'apply_to_frozen'=> true,
                'priority'       => 2,
                'is_active'      => true,
            ]
        );
    }

    private function seedFixedFee(): void
    {
        FixedShippingFee::firstOrCreate(
            ['channel_id' => null],
            [
                'fee_amount' => 600,
                'label'      => '通常送料',
                'priority'   => 0,
                'is_active'  => true,
            ]
        );
    }

    private function seedBulkBonus(): void
    {
        $rules = [
            ['buy_qty' => 10, 'free_qty' => 1],
            ['buy_qty' => 20, 'free_qty' => 2],
            ['buy_qty' => 30, 'free_qty' => 3],
        ];

        foreach ($rules as $rule) {
            BulkBonusRule::firstOrCreate(
                ['channel_id' => null, 'product_id' => null, 'buy_qty' => $rule['buy_qty']],
                [
                    'free_qty'           => $rule['free_qty'],
                    'max_free_per_order' => null,
                    'stackable'          => true,
                    'priority'           => 0,
                    'is_active'          => true,
                ]
            );
        }
    }

    private function seedPrefectureRates(): void
    {
        $prefectures = [
            ['01','北海道','Hokkaido',        1400,1700,2000,2400,2800],
            ['02','青森県','Aomori',           1000,1200,1500,1800,2200],
            ['03','岩手県','Iwate',            1000,1200,1500,1800,2200],
            ['04','宮城県','Miyagi',            900,1100,1400,1700,2100],
            ['05','秋田県','Akita',            1000,1200,1500,1800,2200],
            ['06','山形県','Yamagata',          900,1100,1400,1700,2100],
            ['07','福島県','Fukushima',         900,1100,1400,1700,2100],
            ['08','茨城県','Ibaraki',           800,1000,1200,1500,1900],
            ['09','栃木県','Tochigi',           800,1000,1200,1500,1900],
            ['10','群馬県','Gunma',             800,1000,1200,1500,1900],
            ['11','埼玉県','Saitama',           700, 900,1100,1400,1800],
            ['12','千葉県','Chiba',             700, 900,1100,1400,1800],
            ['13','東京都','Tokyo',             700, 900,1100,1400,1800],
            ['14','神奈川県','Kanagawa',        700, 900,1100,1400,1800],
            ['15','新潟県','Niigata',           900,1100,1400,1700,2100],
            ['16','富山県','Toyama',            900,1100,1400,1700,2100],
            ['17','石川県','Ishikawa',          900,1100,1400,1700,2100],
            ['18','福井県','Fukui',             900,1100,1400,1700,2100],
            ['19','山梨県','Yamanashi',         800,1000,1200,1500,1900],
            ['20','長野県','Nagano',            800,1000,1200,1500,1900],
            ['21','岐阜県','Gifu',              850,1050,1300,1600,2000],
            ['22','静岡県','Shizuoka',          800,1000,1200,1500,1900],
            ['23','愛知県','Aichi',             800,1000,1200,1500,1900],
            ['24','三重県','Mie',               850,1050,1300,1600,2000],
            ['25','滋賀県','Shiga',             850,1050,1300,1600,2000],
            ['26','京都府','Kyoto',             850,1050,1300,1600,2000],
            ['27','大阪府','Osaka',             850,1050,1300,1600,2000],
            ['28','兵庫県','Hyogo',             850,1050,1300,1600,2000],
            ['29','奈良県','Nara',              900,1100,1400,1700,2100],
            ['30','和歌山県','Wakayama',        900,1100,1400,1700,2100],
            ['31','鳥取県','Tottori',           950,1150,1450,1750,2150],
            ['32','島根県','Shimane',           950,1150,1450,1750,2150],
            ['33','岡山県','Okayama',           900,1100,1400,1700,2100],
            ['34','広島県','Hiroshima',         900,1100,1400,1700,2100],
            ['35','山口県','Yamaguchi',         950,1150,1450,1750,2150],
            ['36','徳島県','Tokushima',         950,1150,1450,1750,2150],
            ['37','香川県','Kagawa',            950,1150,1450,1750,2150],
            ['38','愛媛県','Ehime',             950,1150,1450,1750,2150],
            ['39','高知県','Kochi',             950,1150,1450,1750,2150],
            ['40','福岡県','Fukuoka',           900,1100,1400,1700,2100],
            ['41','佐賀県','Saga',             1000,1200,1500,1800,2200],
            ['42','長崎県','Nagasaki',         1000,1200,1500,1800,2200],
            ['43','熊本県','Kumamoto',         1000,1200,1500,1800,2200],
            ['44','大分県','Oita',             1000,1200,1500,1800,2200],
            ['45','宮崎県','Miyazaki',         1050,1250,1550,1850,2250],
            ['46','鹿児島県','Kagoshima',      1050,1250,1550,1850,2250],
            ['47','沖縄県','Okinawa',          1600,1900,2200,2600,3000],
        ];

        $ranges = [
            [1,  10],
            [11, 20],
            [21, 30],
            [31, 50],
            [51, 9999],
        ];

        foreach ($prefectures as $p) {
            [$code, $nameJa, $nameEn, $r1, $r2, $r3, $r4, $r5] = $p;
            $prices = [$r1, $r2, $r3, $r4, $r5];

            foreach ($ranges as $i => [$from, $to]) {
                PrefectureShippingRate::firstOrCreate(
                    [
                        'channel_id'      => null,
                        'prefecture_code' => $code,
                        'qty_from'        => $from,
                        'qty_to'          => $to,
                    ],
                    [
                        'prefecture_name_ja' => $nameJa,
                        'prefecture_name_en' => $nameEn,
                        'shipping_price'     => $prices[$i],
                        'priority'           => 0,
                        'is_active'          => true,
                    ]
                );
            }
        }
    }
}
