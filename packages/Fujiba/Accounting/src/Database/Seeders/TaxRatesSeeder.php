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
            [
                'code' => 'withholding_b',
                'category' => 'withholding',
                'name_ja' => '源泉徴収税額表(乙欄) 月額',
                'name_en' => 'Withholding Income Tax (Class B, Secondary Income)',
                'name_my' => 'ဝင်ငွေခွန် ဖြတ်တောက် (အမျိုးအစား B - ဒုတိယ ဝင်ငွေ)', // MY-REVIEW
                'rate' => 10.2100,                              // 10% + 0.21% 復興特別所得税 = 10.21% flat
                'brackets' => $this->withholdingBracketsB(),
            ],
            [
                'code' => 'withholding_c',
                'category' => 'withholding',
                'name_ja' => '源泉徴収税額表(丙欄) 日額',
                'name_en' => 'Withholding Income Tax (Class C, Daily Workers)',
                'name_my' => 'ဝင်ငွေခွန် ဖြတ်တောက် (အမျိုးအစား C - နေ့စား)', // MY-REVIEW
                'rate' => null,
                'brackets' => $this->withholdingBracketsC(),
            ],
            [
                'code' => 'bonus_withholding_rate',
                'category' => 'withholding',
                'name_ja' => '賞与に対する源泉徴収税額の算出率の表',
                'name_en' => 'Bonus Withholding Rate Table (lookup by prior-month taxable)',
                'name_my' => 'ဆုကြေး အခွန်နှုန်း ဇယား', // MY-REVIEW
                'rate' => null,
                'brackets' => $this->bonusWithholdingBrackets(),
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

    /**
     * 乙欄 (Class B) — secondary income / 副業.
     *
     * Real 国税庁 乙欄 is a long bracket table; in practice it converges to
     * the simple flat 10.21% (10% income tax + 0.21% 復興特別所得税) above
     * the daily ¥3,500 threshold. Sprint 6 ships this approximation —
     * sufficient for monthly part-timer secondary income; precise table
     * lookup is deferred to Sprint 7 if a tax accountant flags it.
     */
    protected function withholdingBracketsB(): array
    {
        return [
            ['taxable_min' => 0,       'taxable_max' => null, 'rate' => 10.21, 'deduction' => 0],
        ];
    }

    /**
     * 丙欄 (Class C) — daily workers, paid per-day not per-month.
     *
     * Daily ¥9,300 と未満は非課税 (excluded from withholding).
     * 超過分は段階課税。実装は給与計算時に「日割換算」で適用される
     * (PayrollCalculator::withholding() で `month/working_days` で平均化)。
     */
    protected function withholdingBracketsC(): array
    {
        return [
            ['taxable_min' => 0,     'taxable_max' => 9299,  'rate' => 0.00, 'deduction' => 0],
            ['taxable_min' => 9300,  'taxable_max' => 11299, 'rate' => 3.063,'deduction' => 0],   // 3% + 復興税
            ['taxable_min' => 11300, 'taxable_max' => 13299, 'rate' => 5.105,'deduction' => 231],
            ['taxable_min' => 13300, 'taxable_max' => null,  'rate' => 7.147,'deduction' => 502],
        ];
    }

    /**
     * 賞与に対する源泉徴収税額の算出率の表 — looked up by prior-month
     * taxable income (給与額 − 社保) and dependents_count, returns a
     * percentage to apply to the bonus amount.
     *
     * Stored as a simplified ladder: prior_month_taxable bracket → rate%.
     * 扶養 0 人を基準とし、calculator が扶養人数で taxable_min を引き下げる
     * (¥31,667 / 人、Sprint 4 と同じ approximation)。
     */
    protected function bonusWithholdingBrackets(): array
    {
        return [
            ['prev_taxable_min' => 0,        'prev_taxable_max' => 67999,    'rate' => 0.00],
            ['prev_taxable_min' => 68000,    'prev_taxable_max' => 78999,    'rate' => 2.042],
            ['prev_taxable_min' => 79000,    'prev_taxable_max' => 251999,   'rate' => 4.084],
            ['prev_taxable_min' => 252000,   'prev_taxable_max' => 333999,   'rate' => 6.126],
            ['prev_taxable_min' => 334000,   'prev_taxable_max' => 362999,   'rate' => 8.168],
            ['prev_taxable_min' => 363000,   'prev_taxable_max' => 394999,   'rate' => 10.21],
            ['prev_taxable_min' => 395000,   'prev_taxable_max' => 419999,   'rate' => 12.252],
            ['prev_taxable_min' => 420000,   'prev_taxable_max' => 549999,   'rate' => 14.294],
            ['prev_taxable_min' => 550000,   'prev_taxable_max' => 647999,   'rate' => 16.336],
            ['prev_taxable_min' => 648000,   'prev_taxable_max' => 699999,   'rate' => 18.378],
            ['prev_taxable_min' => 700000,   'prev_taxable_max' => 777999,   'rate' => 20.42],
            ['prev_taxable_min' => 778000,   'prev_taxable_max' => 899999,   'rate' => 22.462],
            ['prev_taxable_min' => 900000,   'prev_taxable_max' => 1144999,  'rate' => 24.504],
            ['prev_taxable_min' => 1145000,  'prev_taxable_max' => 1499999,  'rate' => 26.546],
            ['prev_taxable_min' => 1500000,  'prev_taxable_max' => 1699999,  'rate' => 28.588],
            ['prev_taxable_min' => 1700000,  'prev_taxable_max' => 2169999,  'rate' => 30.63],
            ['prev_taxable_min' => 2170000,  'prev_taxable_max' => 2899999,  'rate' => 32.672],
            ['prev_taxable_min' => 2900000,  'prev_taxable_max' => 3499999,  'rate' => 35.735],
            ['prev_taxable_min' => 3500000,  'prev_taxable_max' => null,     'rate' => 40.84],
        ];
    }
}
