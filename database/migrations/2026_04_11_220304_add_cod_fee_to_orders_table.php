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
            if (! Schema::hasColumn('orders', 'cod_fee')) {
                $table->decimal('cod_fee', 12, 4)->default(0)->after('base_grand_total');
            }

            if (! Schema::hasColumn('orders', 'base_cod_fee')) {
                $table->decimal('base_cod_fee', 12, 4)->default(0)->after('cod_fee');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'cod_fee')) {
                $table->dropColumn('cod_fee');
            }

            if (Schema::hasColumn('orders', 'base_cod_fee')) {
                $table->dropColumn('base_cod_fee');
            }
        });
    }
};
