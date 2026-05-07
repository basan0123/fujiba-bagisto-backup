<?php

namespace Fujiba\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;

class AccountingDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            FiscalPeriodSeeder::class,
            ChartOfAccountsSeeder::class,
            TaxRatesSeeder::class,
        ]);
    }
}
