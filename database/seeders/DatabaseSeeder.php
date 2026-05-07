<?php

namespace Database\Seeders;

use Fujiba\Accounting\Database\Seeders\AccountingDatabaseSeeder;
use Illuminate\Database\Seeder;
use Webkul\Installer\Database\Seeders\DatabaseSeeder as BagistoDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(BagistoDatabaseSeeder::class);
        $this->call(AccountingDatabaseSeeder::class);
    }
}
