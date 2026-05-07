<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('free_shipping_thresholds')) {
            if (! Schema::hasColumn('free_shipping_thresholds', 'prefecture_codes')) {
                Schema::table('free_shipping_thresholds', function (Blueprint $table) {
                    $table->json('prefecture_codes')->nullable()->after('excluded_categories');
                });
            }

            if (! Schema::hasColumn('free_shipping_thresholds', 'base_shipping_fee')) {
                Schema::table('free_shipping_thresholds', function (Blueprint $table) {
                    $table->decimal('base_shipping_fee', 10, 2)->default(0)->after('prefecture_codes');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('free_shipping_thresholds')) {
            Schema::table('free_shipping_thresholds', function (Blueprint $table) {
                $table->dropColumn(['prefecture_codes', 'base_shipping_fee']);
            });
        }
    }
};
