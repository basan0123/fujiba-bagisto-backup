<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'tiktok_open_id')) {
                $table->string('tiktok_open_id', 100)->nullable()->after('email');
            }
        });

        if (! Schema::hasIndex('customers', 'customers_tiktok_open_id_index')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->index('tiktok_open_id', 'customers_tiktok_open_id_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('customers', 'customers_tiktok_open_id_index')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropIndex('customers_tiktok_open_id_index');
            });
        }

        if (Schema::hasColumn('customers', 'tiktok_open_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('tiktok_open_id');
            });
        }
    }
};
