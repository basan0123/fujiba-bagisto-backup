<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 7 Phase B — backfill plumbing.
     *
     *   • status enum gains 'correction'
     *   • new corrected_from_slip_id column → links a correction back to the
     *     original buggy slip
     *   • relax (staff_id, year_month, is_bonus) unique to include status,
     *     so the same (staff, month, kind=monthly) can have an original
     *     finalized slip + a correction slip side by side.
     */
    public function up(): void
    {
        // 1) Widen the status enum.
        DB::statement("ALTER TABLE `fujiba_payroll_slips`
                       MODIFY `status` ENUM('draft','finalized','paid','correction')
                       NOT NULL DEFAULT 'draft'");

        // 2) Add corrected_from_slip_id (nullable, soft FK — buggy slip rows
        //    are immutable so cascade rules don't matter).
        Schema::table('fujiba_payroll_slips', function (Blueprint $table) {
            if (! Schema::hasColumn('fujiba_payroll_slips', 'corrected_from_slip_id')) {
                $table->unsignedBigInteger('corrected_from_slip_id')->nullable()->after('journal_entry_id');
                $table->index('corrected_from_slip_id', 'fujiba_payroll_slips_corrected_from_idx');
            }
        });

        // 3) Drop Phase 3A's (staff_id, year_month, is_bonus) unique and re-create
        //    with status appended so corrections coexist with the original slip.
        $hadOld = ! empty(DB::select(
            "SHOW INDEX FROM fujiba_payroll_slips
             WHERE Key_name = 'fujiba_payroll_slips_staff_year_month_isbonus_unique'"
        ));
        if ($hadOld) {
            DB::statement('ALTER TABLE `fujiba_payroll_slips`
                           DROP INDEX `fujiba_payroll_slips_staff_year_month_isbonus_unique`');
        }
        $hasNew = ! empty(DB::select(
            "SHOW INDEX FROM fujiba_payroll_slips
             WHERE Key_name = 'fujiba_payroll_slips_staff_ym_bonus_status_unique'"
        ));
        if (! $hasNew) {
            DB::statement('ALTER TABLE `fujiba_payroll_slips`
                           ADD CONSTRAINT `fujiba_payroll_slips_staff_ym_bonus_status_unique`
                           UNIQUE (`staff_id`, `year_month`, `is_bonus`, `status`)');
        }
    }

    public function down(): void
    {
        $hasNew = ! empty(DB::select(
            "SHOW INDEX FROM fujiba_payroll_slips
             WHERE Key_name = 'fujiba_payroll_slips_staff_ym_bonus_status_unique'"
        ));
        if ($hasNew) {
            DB::statement('ALTER TABLE `fujiba_payroll_slips`
                           DROP INDEX `fujiba_payroll_slips_staff_ym_bonus_status_unique`');
        }

        // Restore the Sprint 6 unique on (staff_id, year_month, is_bonus).
        DB::statement('ALTER TABLE `fujiba_payroll_slips`
                       ADD CONSTRAINT `fujiba_payroll_slips_staff_year_month_isbonus_unique`
                       UNIQUE (`staff_id`, `year_month`, `is_bonus`)');

        Schema::table('fujiba_payroll_slips', function (Blueprint $table) {
            if (Schema::hasColumn('fujiba_payroll_slips', 'corrected_from_slip_id')) {
                $table->dropIndex('fujiba_payroll_slips_corrected_from_idx');
                $table->dropColumn('corrected_from_slip_id');
            }
        });

        // Narrow the status enum back. Forces existing 'correction' rows out,
        // which is intentional — running `down()` is itself a destructive op.
        DB::statement("ALTER TABLE `fujiba_payroll_slips`
                       MODIFY `status` ENUM('draft','finalized','paid')
                       NOT NULL DEFAULT 'draft'");
    }
};
