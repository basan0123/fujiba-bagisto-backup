<?php

namespace Fujiba\Accounting\Database\Seeders;

use Fujiba\Accounting\Models\FiscalPeriod;
use Illuminate\Database\Seeder;

class FiscalPeriodSeeder extends Seeder
{
    public function run(): void
    {
        FiscalPeriod::updateOrCreate(
            ['start_date' => '2025-04-01', 'end_date' => '2026-03-31'],
            ['name' => '令和7年度 (2025-04 → 2026-03)', 'status' => 'closed']
        );

        FiscalPeriod::updateOrCreate(
            ['start_date' => '2026-04-01', 'end_date' => '2027-03-31'],
            ['name' => '令和8年度 (2026-04 → 2027-03)', 'status' => 'open']
        );
    }
}
