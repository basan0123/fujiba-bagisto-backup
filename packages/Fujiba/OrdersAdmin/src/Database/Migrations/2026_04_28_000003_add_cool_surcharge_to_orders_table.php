<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'cool_surcharge')) {
                $table->decimal('cool_surcharge', 12, 4)->default(0)->after('base_cod_fee');
            }
            if (! Schema::hasColumn('orders', 'base_cool_surcharge')) {
                $table->decimal('base_cool_surcharge', 12, 4)->default(0)->after('cool_surcharge');
            }
            if (! Schema::hasColumn('orders', 'fulfillment_type')) {
                // delivery | pickup | cod | bank_transfer
                $table->string('fulfillment_type', 20)->default('delivery')->after('base_cool_surcharge');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            foreach (['cool_surcharge', 'base_cool_surcharge', 'fulfillment_type'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
