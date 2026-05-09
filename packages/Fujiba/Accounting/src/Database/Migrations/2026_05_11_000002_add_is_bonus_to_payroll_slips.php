<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 6 Phase 3A — bonus calculation as a slip variant.
     *
     * Adds:
     *   is_bonus            : flag, default false (= regular monthly slip)
     *   prev_taxable        : monthly taxable income from the prior month,
     *                         used to look up bonus_withholding_rate bracket.
     *
     * Replaces the old `(staff_id, year_month)` unique with
     * `(staff_id, year_month, is_bonus)` so a staff can have BOTH a monthly
     * and a bonus slip in the same year-month.
     */
    public function up(): void
    {
        Schema::table('fujiba_payroll_slips', function (Blueprint $table) {
            if (! Schema::hasColumn('fujiba_payroll_slips', 'is_bonus')) {
                $table->boolean('is_bonus')->default(false)->after('year_month');
            }
            if (! Schema::hasColumn('fujiba_payroll_slips', 'prev_taxable')) {
                $table->decimal('prev_taxable', 12, 2)->default(0)->after('is_bonus');
            }
        });

        // Drop the old narrower unique and add the wider one. The original
        // unique starts with staff_id and covers the staff_id FK index — we
        // must add a standalone staff_id index first or MySQL refuses the
        // drop ("Cannot drop index … needed in a foreign key constraint").
        $hasStaffIdx = ! empty(DB::select("SHOW INDEX FROM fujiba_payroll_slips WHERE Key_name = 'fujiba_payroll_slips_staff_id_index'"));
        if (! $hasStaffIdx) {
            DB::statement('ALTER TABLE fujiba_payroll_slips ADD INDEX fujiba_payroll_slips_staff_id_index (staff_id)');
        }
        $hasOld = ! empty(DB::select("SHOW INDEX FROM fujiba_payroll_slips WHERE Key_name = 'fujiba_payroll_slips_staff_id_year_month_unique'"));
        if ($hasOld) {
            DB::statement('ALTER TABLE fujiba_payroll_slips DROP INDEX fujiba_payroll_slips_staff_id_year_month_unique');
        }
        $hasNew = ! empty(DB::select("SHOW INDEX FROM fujiba_payroll_slips WHERE Key_name = 'fujiba_payroll_slips_staff_year_month_isbonus_unique'"));
        if (! $hasNew) {
            DB::statement('ALTER TABLE `fujiba_payroll_slips` ADD CONSTRAINT `fujiba_payroll_slips_staff_year_month_isbonus_unique` UNIQUE (`staff_id`, `year_month`, `is_bonus`)');
        }
    }

    public function down(): void
    {
        Schema::table('fujiba_payroll_slips', function (Blueprint $table) {
            if (Schema::hasColumn('fujiba_payroll_slips', 'is_bonus')) {
                $table->dropColumn('is_bonus');
            }
            if (Schema::hasColumn('fujiba_payroll_slips', 'prev_taxable')) {
                $table->dropColumn('prev_taxable');
            }
        });

        // Restore the original (staff_id, year_month) unique.
        $hasOld = ! empty(DB::select("SHOW INDEX FROM fujiba_payroll_slips WHERE Key_name = 'fujiba_payroll_slips_staff_id_year_month_unique'"));
        if (! $hasOld) {
            DB::statement('ALTER TABLE fujiba_payroll_slips ADD UNIQUE fujiba_payroll_slips_staff_id_year_month_unique (staff_id, year_month)');
        }
    }
};
