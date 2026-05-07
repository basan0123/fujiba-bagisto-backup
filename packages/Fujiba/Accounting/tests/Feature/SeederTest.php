<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Models\Account;
use Fujiba\Accounting\Models\FiscalPeriod;
use Fujiba\Accounting\Models\TaxRate;
use Fujiba\Accounting\Tests\TestCase;

class SeederTest extends TestCase
{
    public function test_chart_of_accounts_has_at_least_eighty_rows(): void
    {
        $this->assertGreaterThanOrEqual(80, Account::count());
    }

    public function test_all_accounts_have_japanese_name(): void
    {
        $missing = Account::whereNull('name_ja')->orWhere('name_ja', '')->count();
        $this->assertSame(0, $missing, 'All accounts must have a name_ja');
    }

    public function test_account_codes_are_unique(): void
    {
        $duplicates = Account::query()
            ->selectRaw('code, COUNT(*) AS c')
            ->groupBy('code')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $this->assertCount(0, $duplicates, 'Account codes must be unique: ' . $duplicates->pluck('code')->implode(','));
    }

    public function test_parent_ids_resolve(): void
    {
        $orphanCount = Account::whereNotNull('parent_id')
            ->whereNotIn('parent_id', Account::pluck('id'))
            ->count();
        $this->assertSame(0, $orphanCount, 'Every parent_id must reference an existing account');
    }

    public function test_fiscal_periods_are_seeded(): void
    {
        $this->assertSame(1, FiscalPeriod::where('status', 'open')->count());
        $this->assertGreaterThanOrEqual(2, FiscalPeriod::count());
    }

    public function test_tax_rates_are_seeded(): void
    {
        $this->assertNotNull(TaxRate::where('code', 'consumption_standard')->first());
        $this->assertNotNull(TaxRate::where('code', 'consumption_reduced')->first());
        $this->assertNotNull(TaxRate::where('code', 'withholding_a')->first());

        $withholding = TaxRate::where('code', 'withholding_a')->first();
        $this->assertIsArray($withholding->brackets);
        $this->assertNotEmpty($withholding->brackets);
    }
}
