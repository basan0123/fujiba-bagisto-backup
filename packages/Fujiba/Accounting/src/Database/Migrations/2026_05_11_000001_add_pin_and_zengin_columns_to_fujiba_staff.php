<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 6 — only structural change of the sprint.
     *
     *   pin_hash                  : bcrypt-hashed 4-digit kiosk PIN (Phase 1B)
     *   bank_code                 : 4-digit 全銀協 bank code (Phase 2B Zengin)
     *   bank_branch_code          : 3-digit branch code     (Phase 2B Zengin)
     *   bank_account_holder_kana  : 半角カナ holder name    (Phase 2B Zengin)
     *
     * Sprint 1 already had bank_name / bank_branch / bank_account_type /
     * bank_account_number / bank_account_holder. The 3 new columns above
     * are required by the Zengin 120-byte data record (codes only, not names).
     */
    public function up(): void
    {
        Schema::table('fujiba_staff', function (Blueprint $table) {
            if (! Schema::hasColumn('fujiba_staff', 'pin_hash')) {
                $table->string('pin_hash', 60)->nullable()->after('qr_token');
            }
            if (! Schema::hasColumn('fujiba_staff', 'bank_code')) {
                $table->string('bank_code', 4)->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('fujiba_staff', 'bank_branch_code')) {
                $table->string('bank_branch_code', 3)->nullable()->after('bank_branch');
            }
            if (! Schema::hasColumn('fujiba_staff', 'bank_account_holder_kana')) {
                $table->string('bank_account_holder_kana', 64)->nullable()->after('bank_account_holder');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fujiba_staff', function (Blueprint $table) {
            foreach (['pin_hash', 'bank_code', 'bank_branch_code', 'bank_account_holder_kana'] as $col) {
                if (Schema::hasColumn('fujiba_staff', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
