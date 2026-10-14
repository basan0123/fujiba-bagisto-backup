<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive: lets fi_ai_usage mirror per-attempt ledger.json entries
 * (status, worst-case estimate, idempotent ledger key, preview dir/meta).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fi_ai_usage', function (Blueprint $table) {
            if (! Schema::hasColumn('fi_ai_usage', 'status')) {
                $table->string('status', 32)->nullable()->after('cost_jpy');
            }
            if (! Schema::hasColumn('fi_ai_usage', 'estimate_jpy')) {
                $table->decimal('estimate_jpy', 12, 4)->nullable()->after('status');
            }
            if (! Schema::hasColumn('fi_ai_usage', 'ledger_key')) {
                $table->string('ledger_key', 191)->nullable()->unique('fi_usage_ledger_key_uq')->after('estimate_jpy');
            }
            if (! Schema::hasColumn('fi_ai_usage', 'meta')) {
                $table->json('meta')->nullable()->after('ledger_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fi_ai_usage', function (Blueprint $table) {
            $table->dropUnique('fi_usage_ledger_key_uq');
            $table->dropColumn(['status', 'estimate_jpy', 'ledger_key', 'meta']);
        });
    }
};
