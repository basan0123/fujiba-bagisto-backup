<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pos_order', 'idempotency_key')) {
            Schema::table('pos_order', function (Blueprint $table) {
                $table->string('idempotency_key', 64)->nullable()->after('id');
            });
        }

        if (! Schema::hasIndex('pos_order', 'pos_order_user_idem_unique')) {
            Schema::table('pos_order', function (Blueprint $table) {
                $table->unique(['user_id', 'idempotency_key'], 'pos_order_user_idem_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('pos_order', 'pos_order_user_idem_unique')) {
            Schema::table('pos_order', function (Blueprint $table) {
                $table->dropUnique('pos_order_user_idem_unique');
            });
        }

        if (Schema::hasColumn('pos_order', 'idempotency_key')) {
            Schema::table('pos_order', function (Blueprint $table) {
                $table->dropColumn('idempotency_key');
            });
        }
    }
};
