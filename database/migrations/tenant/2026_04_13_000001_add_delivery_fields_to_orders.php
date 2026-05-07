<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'delivery_date')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->date('delivery_date')->nullable()->after('updated_at');
                $table->string('delivery_time', 30)->nullable()->after('delivery_date');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'delivery_date')) {
                $table->dropColumn('delivery_date');
            }

            if (Schema::hasColumn('orders', 'delivery_time')) {
                $table->dropColumn('delivery_time');
            }
        });
    }
};
