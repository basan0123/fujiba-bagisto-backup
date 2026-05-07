<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONNECTION = 'chatpion_stage';
    private const TABLE      = 'ecommerce_product';
    private const COLUMN     = 'fujiba_decoded_json';

    public function up(): void
    {
        if (! Schema::connection(self::CONNECTION)->hasTable(self::TABLE)) {
            return;
        }

        if (Schema::connection(self::CONNECTION)->hasColumn(self::TABLE, self::COLUMN)) {
            return;
        }

        DB::connection(self::CONNECTION)->statement(sprintf(
            'ALTER TABLE `%s` ADD COLUMN `%s` JSON NULL AFTER `original_product_name`',
            self::TABLE,
            self::COLUMN
        ));
    }

    public function down(): void
    {
        if (! Schema::connection(self::CONNECTION)->hasTable(self::TABLE)) {
            return;
        }

        if (! Schema::connection(self::CONNECTION)->hasColumn(self::TABLE, self::COLUMN)) {
            return;
        }

        DB::connection(self::CONNECTION)->statement(sprintf(
            'ALTER TABLE `%s` DROP COLUMN `%s`',
            self::TABLE,
            self::COLUMN
        ));
    }
};
