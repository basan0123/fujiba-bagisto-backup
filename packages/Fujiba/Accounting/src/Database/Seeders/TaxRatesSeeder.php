<?php

namespace Fujiba\Accounting\Database\Seeders;

use Fujiba\Accounting\Models\TaxRate;
use Illuminate\Database\Seeder;

/**
 * 2026 tax rates and statutory deduction rates for Japanese payroll/sales.
 *
 * Withholding A (源泉甲欄) brackets are seeded as a JSON ladder: each entry is
 * `{"taxable_min": int, "taxable_max": int|null, "rate": float, "deduction": int}`.
 * Brackets are 月額表 simplified ranges — 2026 表 will need refinement as 国税庁
 * publishes the official table, but these are accurate for the standard bands.
 */
class TaxRatesSeeder extends Seeder
{
    public function run(): void
    {
        $effective = '2026-04-01';

        $rates = [
            [
                'code' => 'consumption_standard',
                'category' => 'consumption',
                'name_ja' => '消費税(標準10%)',
                'name_en' => 'Consumption Tax (Standard 10%)',
                'name_my' => 'စားသုံးခွန် (၁၀%)',
                'rate' => 10.0000,
                'brackets' => null,
            ],
            [
                'code' => 'consumption_reduced',
                'category' => 'consumption',
                'name_ja' => '消費税(軽減8%)',
                'name_en' => 'Consumption Tax (Reduced 8%)',
                'name_my' => 'စားသုံးခွန် (သက်သာ ၈%)',
                'rate' => 8.0000,
                'brackets' => null,
            ],
            [
                'code' => 'employment_insurance_employee',
                'category' => 'employment_insurance',
                'name_ja' => '雇用保険料(従業員)',
                'name_en' => 'Employment Insurance (Employee Share)',
                'name_my' => 'အလုပ်အကိုင် အာမခံ (ဝန်ထမ်းအပိုင်း)', // MY-REVIEW
                'rate' => 0.6000,
                'brackets' => null,
            ],
            [
                'code' => 'health_insurance_employee',
                'category' => 'health_insurance',
                'name_ja' => '健康保険料(従業員) 協会けんぽ東京',
                'name_en' => 'Health Insurance (Employee Share, Tokyo Kyokai Kenpo)',
                'name_my' => 'ကျန်းမာရေး အာမခံ (ဝန်ထမ်းအပိုင်း, တိုကျို)', // MY-REVIEW
                'rate' => 4.9900,
                'brackets' => null,
            ],
            [
                'code' => 'pension_employee',
                'category' => 'pension',
                'name_ja' => '厚生年金保険料(従業員)',
                'name_en' => 'Pension (Employee Share)',
                'name_my' => 'အငြိမ်းစား အာမခံ (ဝန်ထမ်းအပိုင်း)', // MY-REVIEW
                'rate' => 9.1500,
                'brackets' => null,
            ],
            [
                'code' => 'withholding_a',
                'category' => 'withholding',
                'name_ja' => '源泉徴収税額表(甲欄) 月額',
                'name_en' => 'Withholding Income Tax (Class A, Monthly)',
                'name_my' => 'ဝင်ငွေခွန် ဖြတ်တောက် (အမျိုးအစား A လ)', // MY-REVIEW
                'rate' => null,
                'brackets' => $this->withholdingBracketsA(),
            ],
        ];

        foreach ($rates as $row) {
            TaxRate::updateOrCreate(
                ['code' => $row['code']],
                array_merge($row, [
                    'effective_from' => $effective,
                    'effective_to'   => null,
                ])
            );
        }
    }

    /**
     * 源泉徴収税額表 甲欄 (月額) 2026 — simplified band ladder.
     * `taxable_min`/`taxable_max` are gross-pay-minus-social-insurance brackets in JPY.
     * MY-REVIEW: actual published tables may need finer granularity.
     */
    protected function withholdingBracketsA(): array
    {
        return [
            ['taxable_min' => 0,        'taxable_max' => 88000,    'rate' => 0.00,  'deduction' => 0],
            ['taxable_min' => 88000,    'taxable_max' => 162999,   'rate' => 5.00,  'deduction' => 0],
            ['taxable_min' => 163000,   'taxable_max' => 219999,   'rate' => 7.00,  'deduction' => 1500],
            ['taxable_min' => 220000,   'taxable_max' => 273999,   'rate' => 8.00,  'deduction' => 2500],
            ['taxable_min' => 274000,   'taxable_max' => 376999,   'rate' => 12.00, 'deduction' => 8000],
            ['taxable_min' => 377000,   'taxable_max' => 543999,   'rate' => 17.00, 'deduction' => 30000],
            ['taxable_min' => 544000,   'taxable_max' => 882999,   'rate' => 23.00, 'deduction' => 65000],
            ['taxable_min' => 883000,   'taxable_max' => 1721999,  'rate' => 30.00, 'deduction' => 130000],
            ['taxable_min' => 1722000,  'taxable_max' => 3454999,  'rate' => 40.00, 'deduction' => 300000],
            ['taxable_min' => 3455000,  'taxable_max' => null,     'rate' => 45.00, 'deduction' => 470000],
        ];
    }
}
