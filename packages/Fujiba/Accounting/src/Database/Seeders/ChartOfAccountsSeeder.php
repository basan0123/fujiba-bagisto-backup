<?php

namespace Fujiba\Accounting\Database\Seeders;

use Fujiba\Accounting\Models\Account;
use Illuminate\Database\Seeder;

/**
 * 標準勘定科目 (Standard Chart of Accounts) for a Japanese SME.
 * Codes follow the conventional 4-digit ranges:
 *   1xxx 資産  / 2xxx 負債  / 3xxx 純資産
 *   4xxx 売上  / 5xxx 売上原価
 *   6xxx 販売費及び一般管理費 (販管費)
 *   7xxx 営業外  / 8xxx 特別  / 9xxx 税金
 *
 * Myanmar names: best-effort transliteration. Where the term has no clean
 * Burmese rendering, I use a reasonable katakana-style transliteration and
 * add `// MY-REVIEW` so a native speaker can replace it later.
 */
class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->rows() as $sort => $row) {
            Account::updateOrCreate(
                ['code' => $row['code']],
                array_merge($row, ['sort_order' => ($sort + 1) * 10])
            );
        }
    }

    /** @return array<int,array<string,mixed>> */
    protected function rows(): array
    {
        return [
            // ───────────────────────── 資産 (Assets) ─────────────────────────
            ['code' => '1110', 'type' => 'asset', 'sub_type' => 'current_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '現金',           'name_en' => 'Cash',                       'name_my' => 'ငွေသား'],
            ['code' => '1120', 'type' => 'asset', 'sub_type' => 'current_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '小口現金',       'name_en' => 'Petty Cash',                 'name_my' => 'အသေးစား ငွေသား'],
            ['code' => '1130', 'type' => 'asset', 'sub_type' => 'current_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '普通預金',       'name_en' => 'Ordinary Deposits',          'name_my' => 'သာမန်ဘဏ်အပ်ငွေ'],
            ['code' => '1131', 'type' => 'asset', 'sub_type' => 'current_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '当座預金',       'name_en' => 'Checking Deposits',          'name_my' => 'လက်ရှိ စာရင်း'], // MY-REVIEW
            ['code' => '1140', 'type' => 'asset', 'sub_type' => 'current_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '定期預金',       'name_en' => 'Time Deposits',              'name_my' => 'အချိန်အပ်ငွေ'],
            ['code' => '1150', 'type' => 'asset', 'sub_type' => 'current_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '売掛金',         'name_en' => 'Accounts Receivable',        'name_my' => 'ရရန်ရှိစာရင်း'],
            ['code' => '1160', 'type' => 'asset', 'sub_type' => 'current_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '受取手形',       'name_en' => 'Notes Receivable',           'name_my' => 'လက်ခံစာချုပ်'], // MY-REVIEW
            ['code' => '1170', 'type' => 'asset', 'sub_type' => 'current_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '未収入金',       'name_en' => 'Other Receivables',          'name_my' => 'အခြားရရန်ငွေ'],
            ['code' => '1180', 'type' => 'asset', 'sub_type' => 'current_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '商品',           'name_en' => 'Inventory (Merchandise)',    'name_my' => 'ကုန်ပစ္စည်း'],
            ['code' => '1181', 'type' => 'asset', 'sub_type' => 'current_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '製品',           'name_en' => 'Finished Goods',             'name_my' => 'ပြီးဆုံးကုန်ပစ္စည်း'], // MY-REVIEW
            ['code' => '1182', 'type' => 'asset', 'sub_type' => 'current_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '原材料',         'name_en' => 'Raw Materials',              'name_my' => 'ကုန်ကြမ်း'],
            ['code' => '1190', 'type' => 'asset', 'sub_type' => 'current_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '前払費用',       'name_en' => 'Prepaid Expenses',           'name_my' => 'ကြိုတင်ပေးချေငွေ'], // MY-REVIEW
            ['code' => '1195', 'type' => 'asset', 'sub_type' => 'current_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '前払金',         'name_en' => 'Advances Paid',              'name_my' => 'ကြိုတင်ငွေပေး'], // MY-REVIEW
            ['code' => '1210', 'type' => 'asset', 'sub_type' => 'current_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '仮払金',         'name_en' => 'Suspense Payments',          'name_my' => 'ယာယီပေးချေငွေ'], // MY-REVIEW
            ['code' => '1220', 'type' => 'asset', 'sub_type' => 'current_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '仮払消費税',     'name_en' => 'Provisional Consumption Tax (Input)', 'name_my' => 'ကြိုတင်စားသုံးခွန်'], // MY-REVIEW
            ['code' => '1230', 'type' => 'asset', 'sub_type' => 'current_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '立替金',         'name_en' => 'Advances on Behalf',         'name_my' => 'ကိုယ်စားပေးချေငွေ'], // MY-REVIEW

            // 固定資産 — 有形
            ['code' => '1510', 'type' => 'asset', 'sub_type' => 'fixed_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '建物',           'name_en' => 'Buildings',                  'name_my' => 'အဆောက်အအုံ'],
            ['code' => '1520', 'type' => 'asset', 'sub_type' => 'fixed_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '建物附属設備',   'name_en' => 'Building Fixtures',          'name_my' => 'အဆောက်အအုံ ဆက်စပ်ပစ္စည်း'], // MY-REVIEW
            ['code' => '1530', 'type' => 'asset', 'sub_type' => 'fixed_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '器具備品',       'name_en' => 'Tools, Furniture & Fixtures','name_my' => 'ကိရိယာနှင့် ရုံးပစ္စည်း'],
            ['code' => '1540', 'type' => 'asset', 'sub_type' => 'fixed_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '車両運搬具',     'name_en' => 'Vehicles',                   'name_my' => 'ယာဉ်များ'],
            ['code' => '1550', 'type' => 'asset', 'sub_type' => 'fixed_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '土地',           'name_en' => 'Land',                       'name_my' => 'မြေ'],
            ['code' => '1590', 'type' => 'asset', 'sub_type' => 'fixed_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '減価償却累計額', 'name_en' => 'Accumulated Depreciation',   'name_my' => 'စုစုပေါင်းတန်ဖိုးလျှော့ခြင်း'], // MY-REVIEW

            // 固定資産 — 無形
            ['code' => '1610', 'type' => 'asset', 'sub_type' => 'intangible_asset', 'tax_category' => 'non_taxable',
             'name_ja' => 'ソフトウェア',   'name_en' => 'Software',                   'name_my' => 'ဆော့ဖ်ဝဲ'],
            ['code' => '1620', 'type' => 'asset', 'sub_type' => 'intangible_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '商標権',         'name_en' => 'Trademarks',                 'name_my' => 'ကုန်အမှတ်တံဆိပ် အခွင့်အရေး'], // MY-REVIEW

            // 投資その他
            ['code' => '1710', 'type' => 'asset', 'sub_type' => 'investment_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '敷金',           'name_en' => 'Lease Deposits',             'name_my' => 'ငှားရမ်းခ စရံငွေ'],
            ['code' => '1720', 'type' => 'asset', 'sub_type' => 'investment_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '保証金',         'name_en' => 'Guarantee Deposits',         'name_my' => 'အာမခံ စရံငွေ'],
            ['code' => '1730', 'type' => 'asset', 'sub_type' => 'investment_asset', 'tax_category' => 'non_taxable',
             'name_ja' => '長期前払費用',   'name_en' => 'Long-term Prepaid Expenses', 'name_my' => 'ရေရှည်ကြိုတင်ပေးချေငွေ'], // MY-REVIEW

            // ───────────────────────── 負債 (Liabilities) ─────────────────────────
            ['code' => '2110', 'type' => 'liability', 'sub_type' => 'current_liability', 'tax_category' => 'non_taxable',
             'name_ja' => '買掛金',         'name_en' => 'Accounts Payable',           'name_my' => 'ပေးရန်ရှိ စာရင်း'],
            ['code' => '2120', 'type' => 'liability', 'sub_type' => 'current_liability', 'tax_category' => 'non_taxable',
             'name_ja' => '支払手形',       'name_en' => 'Notes Payable',              'name_my' => 'ပေးချေရန် စာချုပ်'], // MY-REVIEW
            ['code' => '2130', 'type' => 'liability', 'sub_type' => 'current_liability', 'tax_category' => 'non_taxable',
             'name_ja' => '未払金',         'name_en' => 'Other Payables',             'name_my' => 'အခြားပေးရန်ငွေ'],
            ['code' => '2140', 'type' => 'liability', 'sub_type' => 'current_liability', 'tax_category' => 'non_taxable',
             'name_ja' => '未払費用',       'name_en' => 'Accrued Expenses',           'name_my' => 'မပေးရသေးသော ကုန်ကျစရိတ်'],
            ['code' => '2150', 'type' => 'liability', 'sub_type' => 'current_liability', 'tax_category' => 'non_taxable',
             'name_ja' => '前受金',         'name_en' => 'Advances Received',          'name_my' => 'ကြိုတင်လက်ခံငွေ'],
            ['code' => '2160', 'type' => 'liability', 'sub_type' => 'current_liability', 'tax_category' => 'non_taxable',
             'name_ja' => '前受収益',       'name_en' => 'Unearned Revenue',           'name_my' => 'ကြိုတင်လက်ခံ ဝင်ငွေ'], // MY-REVIEW
            ['code' => '2170', 'type' => 'liability', 'sub_type' => 'current_liability', 'tax_category' => 'non_taxable',
             'name_ja' => '仮受金',         'name_en' => 'Suspense Receipts',          'name_my' => 'ယာယီလက်ခံငွေ'], // MY-REVIEW
            ['code' => '2180', 'type' => 'liability', 'sub_type' => 'current_liability', 'tax_category' => 'non_taxable',
             'name_ja' => '仮受消費税',     'name_en' => 'Provisional Consumption Tax (Output)', 'name_my' => 'လက်ခံစားသုံးခွန်'], // MY-REVIEW

            // 預り金 (withholdings)
            ['code' => '2210', 'type' => 'liability', 'sub_type' => 'current_liability', 'tax_category' => 'non_taxable',
             'name_ja' => '預り金(社保)', 'name_en' => 'Withholding (Social Insurance)','name_my' => 'လက်ခံထိန်းငွေ - လူမှုဖူလုံ'], // MY-REVIEW
            ['code' => '2220', 'type' => 'liability', 'sub_type' => 'current_liability', 'tax_category' => 'non_taxable',
             'name_ja' => '預り金(源泉)', 'name_en' => 'Withholding (Income Tax)',     'name_my' => 'လက်ခံထိန်းငွေ - ဝင်ငွေခွန်'], // MY-REVIEW
            ['code' => '2230', 'type' => 'liability', 'sub_type' => 'current_liability', 'tax_category' => 'non_taxable',
             'name_ja' => '預り金(住民税)','name_en' => 'Withholding (Resident Tax)',  'name_my' => 'လက်ခံထိန်းငွေ - နေထိုင်သူခွန်'], // MY-REVIEW
            ['code' => '2240', 'type' => 'liability', 'sub_type' => 'current_liability', 'tax_category' => 'non_taxable',
             'name_ja' => '預り金(その他)','name_en' => 'Withholding (Other)',         'name_my' => 'လက်ခံထိန်းငွေ - အခြား'], // MY-REVIEW

            ['code' => '2310', 'type' => 'liability', 'sub_type' => 'current_liability', 'tax_category' => 'non_taxable',
             'name_ja' => '短期借入金',     'name_en' => 'Short-term Loans Payable',   'name_my' => 'တိုတောင်းချေးငွေ'],
            ['code' => '2320', 'type' => 'liability', 'sub_type' => 'current_liability', 'tax_category' => 'non_taxable',
             'name_ja' => '未払法人税等',   'name_en' => 'Income Taxes Payable',       'name_my' => 'ပေးရန်ရှိ ကုမ္ပဏီခွန်'],
            ['code' => '2330', 'type' => 'liability', 'sub_type' => 'current_liability', 'tax_category' => 'non_taxable',
             'name_ja' => '未払消費税',     'name_en' => 'Consumption Tax Payable',    'name_my' => 'ပေးရန်ရှိ စားသုံးခွန်'], // MY-REVIEW

            ['code' => '2510', 'type' => 'liability', 'sub_type' => 'fixed_liability', 'tax_category' => 'non_taxable',
             'name_ja' => '長期借入金',     'name_en' => 'Long-term Loans Payable',    'name_my' => 'ရေရှည်ချေးငွေ'],
            ['code' => '2520', 'type' => 'liability', 'sub_type' => 'fixed_liability', 'tax_category' => 'non_taxable',
             'name_ja' => '退職給付引当金', 'name_en' => 'Retirement Benefit Reserve', 'name_my' => 'အငြိမ်းစား အကျိုးခံစားခွင့် သီးသန့်ငွေ'], // MY-REVIEW

            // ───────────────────────── 純資産 (Equity) ─────────────────────────
            ['code' => '3110', 'type' => 'equity', 'sub_type' => 'capital', 'tax_category' => 'non_taxable',
             'name_ja' => '資本金',         'name_en' => 'Capital Stock',              'name_my' => 'အရင်းအနှီး'],
            ['code' => '3120', 'type' => 'equity', 'sub_type' => 'capital', 'tax_category' => 'non_taxable',
             'name_ja' => '資本準備金',     'name_en' => 'Capital Reserve',            'name_my' => 'အရင်းအနှီး သီးသန့်ငွေ'], // MY-REVIEW
            ['code' => '3210', 'type' => 'equity', 'sub_type' => 'reserve', 'tax_category' => 'non_taxable',
             'name_ja' => '利益準備金',     'name_en' => 'Earned Surplus Reserve',     'name_my' => 'အမြတ် သီးသန့်ငွေ'], // MY-REVIEW
            ['code' => '3220', 'type' => 'equity', 'sub_type' => 'reserve', 'tax_category' => 'non_taxable',
             'name_ja' => '繰越利益剰余金', 'name_en' => 'Retained Earnings',          'name_my' => 'ကျန်ရှိအမြတ်'],

            // ───────────────────────── 売上 (Revenue) ─────────────────────────
            ['code' => '4110', 'type' => 'revenue', 'sub_type' => 'sales', 'tax_category' => 'standard',
             'name_ja' => '売上高(10%)',  'name_en' => 'Sales (10%)',                'name_my' => 'အရောင်း (၁၀%)'],
            ['code' => '4120', 'type' => 'revenue', 'sub_type' => 'sales', 'tax_category' => 'reduced',
             'name_ja' => '売上高(軽減8%)','name_en' => 'Sales (Reduced 8%)',         'name_my' => 'အရောင်း (သက်သာ ၈%)'],
            ['code' => '4130', 'type' => 'revenue', 'sub_type' => 'sales', 'tax_category' => 'out_of_scope',
             'name_ja' => '売上高(非課税)','name_en' => 'Sales (Exempt)',             'name_my' => 'အရောင်း (ခွန်ကင်းလွတ်)'], // MY-REVIEW
            ['code' => '4190', 'type' => 'revenue', 'sub_type' => 'sales', 'tax_category' => 'standard',
             'name_ja' => '売上値引戻り',   'name_en' => 'Sales Returns & Allowances', 'name_my' => 'အရောင်း လျှော့စျေး/ပြန်'], // MY-REVIEW

            // ───────────────────────── 売上原価 (COGS) ─────────────────────────
            ['code' => '5110', 'type' => 'expense', 'sub_type' => 'cogs', 'tax_category' => 'standard',
             'name_ja' => '仕入高',         'name_en' => 'Purchases',                  'name_my' => 'ဝယ်ယူမှု'],
            ['code' => '5120', 'type' => 'expense', 'sub_type' => 'cogs', 'tax_category' => 'reduced',
             'name_ja' => '仕入高(軽減8%)','name_en' => 'Purchases (Reduced 8%)',     'name_my' => 'ဝယ်ယူမှု (သက်သာ ၈%)'], // MY-REVIEW
            ['code' => '5210', 'type' => 'expense', 'sub_type' => 'cogs', 'tax_category' => 'non_taxable',
             'name_ja' => '期首商品棚卸高', 'name_en' => 'Beginning Inventory',        'name_my' => 'အစ ကုန်ပစ္စည်း လက်ကျန်'], // MY-REVIEW
            ['code' => '5220', 'type' => 'expense', 'sub_type' => 'cogs', 'tax_category' => 'non_taxable',
             'name_ja' => '期末商品棚卸高', 'name_en' => 'Ending Inventory',           'name_my' => 'အဆုံး ကုန်ပစ္စည်း လက်ကျန်'], // MY-REVIEW

            // ───────────────────────── 販管費 (SG&A) ─────────────────────────
            ['code' => '6110', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'non_taxable',
             'name_ja' => '役員報酬',       'name_en' => 'Directors Compensation',     'name_my' => 'ဒါရိုက်တာ လစာ'],
            ['code' => '6120', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'non_taxable',
             'name_ja' => '給料手当',       'name_en' => 'Salaries & Wages',           'name_my' => 'လစာများ'],
            ['code' => '6130', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'non_taxable',
             'name_ja' => '雑給',           'name_en' => 'Part-time Wages',            'name_my' => 'အချိန်ပိုင်း လစာ'],
            ['code' => '6140', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'non_taxable',
             'name_ja' => '賞与',           'name_en' => 'Bonuses',                    'name_my' => 'ဆုငွေ'],
            ['code' => '6150', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'non_taxable',
             'name_ja' => '法定福利費',     'name_en' => 'Statutory Welfare Expense',  'name_my' => 'ဥပဒေအရ လူမှုဖူလုံ စရိတ်'], // MY-REVIEW
            ['code' => '6160', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'standard',
             'name_ja' => '福利厚生費',     'name_en' => 'Welfare Expenses',           'name_my' => 'ဝန်ထမ်း အကျိုးခံစားခွင့်'],
            ['code' => '6170', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'standard',
             'name_ja' => '採用教育費',     'name_en' => 'Recruitment & Training',     'name_my' => 'အသစ်ခေါ်ယူ/သင်တန်း စရိတ်'],
            ['code' => '6210', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'standard',
             'name_ja' => '旅費交通費',     'name_en' => 'Travel & Transport',         'name_my' => 'ခရီးစရိတ်'],
            ['code' => '6220', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'standard',
             'name_ja' => '通信費',         'name_en' => 'Communication',              'name_my' => 'ဆက်သွယ်ရေး စရိတ်'],
            ['code' => '6230', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'standard',
             'name_ja' => '交際費',         'name_en' => 'Entertainment',              'name_my' => 'ဧည့်ခံ စရိတ်'],
            ['code' => '6240', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'standard',
             'name_ja' => '会議費',         'name_en' => 'Meeting Expenses',           'name_my' => 'အစည်းအဝေး စရိတ်'],
            ['code' => '6250', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'standard',
             'name_ja' => '水道光熱費',     'name_en' => 'Utilities',                  'name_my' => 'ရေ/မီး/ဓာတ်ငွေ့ စရိတ်'],
            ['code' => '6260', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'standard',
             'name_ja' => '消耗品費',       'name_en' => 'Supplies',                   'name_my' => 'အသုံးအဆောင်ပစ္စည်း'],
            ['code' => '6261', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'standard',
             'name_ja' => '事務用品費',     'name_en' => 'Office Supplies',            'name_my' => 'ရုံးသုံးပစ္စည်း'],
            ['code' => '6270', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'standard',
             'name_ja' => '修繕費',         'name_en' => 'Repair Expenses',            'name_my' => 'ပြုပြင် စရိတ်'],
            ['code' => '6280', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'non_taxable',
             'name_ja' => '地代家賃',       'name_en' => 'Rent',                       'name_my' => 'အခန်းခ'],
            ['code' => '6290', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'standard',
             'name_ja' => 'リース料',       'name_en' => 'Lease Expense',              'name_my' => 'ငှားရမ်းခ'], // MY-REVIEW
            ['code' => '6310', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'non_taxable',
             'name_ja' => '保険料',         'name_en' => 'Insurance',                  'name_my' => 'အာမခံ စရိတ်'],
            ['code' => '6320', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'non_taxable',
             'name_ja' => '租税公課',       'name_en' => 'Taxes & Public Charges',     'name_my' => 'အခွန်နှင့် အစိုးရ ကြေးငွေ'], // MY-REVIEW
            ['code' => '6330', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'standard',
             'name_ja' => '支払手数料',     'name_en' => 'Bank/Service Fees',          'name_my' => 'ဝန်ဆောင်ခ'],
            ['code' => '6340', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'standard',
             'name_ja' => '広告宣伝費',     'name_en' => 'Advertising',                'name_my' => 'ကြော်ငြာ စရိတ်'],
            ['code' => '6350', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'standard',
             'name_ja' => '荷造運賃',       'name_en' => 'Packaging & Freight',        'name_my' => 'ထုပ်ပိုး/သယ်ယူ စရိတ်'],
            ['code' => '6360', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'standard',
             'name_ja' => '車両費',         'name_en' => 'Vehicle Expenses',           'name_my' => 'ယာဉ် စရိတ်'],
            ['code' => '6370', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'standard',
             'name_ja' => '新聞図書費',     'name_en' => 'Books & Subscriptions',      'name_my' => 'စာအုပ်/စာရင်းသွင်း စရိတ်'], // MY-REVIEW
            ['code' => '6380', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'standard',
             'name_ja' => '諸会費',         'name_en' => 'Membership Fees',            'name_my' => 'အဖွဲ့ဝင် ကြေး'], // MY-REVIEW
            ['code' => '6390', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'standard',
             'name_ja' => '雑費',           'name_en' => 'Miscellaneous Expense',      'name_my' => 'အထွေထွေ စရိတ်'],
            ['code' => '6410', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'non_taxable',
             'name_ja' => '減価償却費',     'name_en' => 'Depreciation Expense',       'name_my' => 'တန်ဖိုးလျှော့ စရိတ်'], // MY-REVIEW
            ['code' => '6420', 'type' => 'expense', 'sub_type' => 'sga', 'tax_category' => 'non_taxable',
             'name_ja' => '貸倒損失',       'name_en' => 'Bad Debt Expense',           'name_my' => 'မပြန်ရသော ချေးငွေ'], // MY-REVIEW

            // ───────────────────────── 営業外 (Non-operating) ─────────────────────────
            ['code' => '7110', 'type' => 'revenue', 'sub_type' => 'non_op_revenue', 'tax_category' => 'non_taxable',
             'name_ja' => '受取利息',       'name_en' => 'Interest Income',            'name_my' => 'အတိုးဝင်ငွေ'],
            ['code' => '7120', 'type' => 'revenue', 'sub_type' => 'non_op_revenue', 'tax_category' => 'standard',
             'name_ja' => '雑収入',         'name_en' => 'Miscellaneous Income',       'name_my' => 'အထွေထွေ ဝင်ငွေ'],
            ['code' => '7130', 'type' => 'revenue', 'sub_type' => 'non_op_revenue', 'tax_category' => 'non_taxable',
             'name_ja' => '為替差益',       'name_en' => 'Foreign Exchange Gain',      'name_my' => 'ငွေလဲနှုန်း အကျိုးစီးပွား'], // MY-REVIEW

            ['code' => '7210', 'type' => 'expense', 'sub_type' => 'non_op_expense', 'tax_category' => 'non_taxable',
             'name_ja' => '支払利息',       'name_en' => 'Interest Expense',           'name_my' => 'အတိုးပေးချေငွေ'],
            ['code' => '7220', 'type' => 'expense', 'sub_type' => 'non_op_expense', 'tax_category' => 'non_taxable',
             'name_ja' => '為替差損',       'name_en' => 'Foreign Exchange Loss',      'name_my' => 'ငွေလဲနှုန်း ဆုံးရှုံး'], // MY-REVIEW
            ['code' => '7230', 'type' => 'expense', 'sub_type' => 'non_op_expense', 'tax_category' => 'standard',
             'name_ja' => '雑損失',         'name_en' => 'Miscellaneous Loss',         'name_my' => 'အထွေထွေ ဆုံးရှုံးမှု'],

            // ───────────────────────── 特別 (Special) ─────────────────────────
            ['code' => '8110', 'type' => 'revenue', 'sub_type' => 'special_revenue', 'tax_category' => 'non_taxable',
             'name_ja' => '固定資産売却益', 'name_en' => 'Gain on Sale of Fixed Assets','name_my' => 'ပိုင်ဆိုင်မှု ရောင်းချ အကျိုးအမြတ်'], // MY-REVIEW
            ['code' => '8210', 'type' => 'expense', 'sub_type' => 'special_expense', 'tax_category' => 'non_taxable',
             'name_ja' => '固定資産売却損', 'name_en' => 'Loss on Sale of Fixed Assets','name_my' => 'ပိုင်ဆိုင်မှု ရောင်းချ ဆုံးရှုံးမှု'], // MY-REVIEW

            // ───────────────────────── 税金 (Income taxes) ─────────────────────────
            ['code' => '9110', 'type' => 'expense', 'sub_type' => 'income_tax', 'tax_category' => 'non_taxable',
             'name_ja' => '法人税等',       'name_en' => 'Corporate Income Tax',       'name_my' => 'ကုမ္ပဏီ ဝင်ငွေခွန်'],
            ['code' => '9120', 'type' => 'expense', 'sub_type' => 'income_tax', 'tax_category' => 'non_taxable',
             'name_ja' => '法人住民税',     'name_en' => 'Corporate Resident Tax',     'name_my' => 'ကုမ္ပဏီ နေထိုင်သူ ခွန်'], // MY-REVIEW
            ['code' => '9130', 'type' => 'expense', 'sub_type' => 'income_tax', 'tax_category' => 'non_taxable',
             'name_ja' => '法人事業税',     'name_en' => 'Corporate Enterprise Tax',   'name_my' => 'ကုမ္ပဏီ စီးပွားရေး ခွန်'], // MY-REVIEW
        ];
    }
}
