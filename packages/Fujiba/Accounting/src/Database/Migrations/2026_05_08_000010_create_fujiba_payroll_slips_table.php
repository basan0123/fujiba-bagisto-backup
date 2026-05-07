<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fujiba_payroll_slips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->string('year_month', 7);     // "2026-05"
            $table->date('pay_date')->nullable();
            $table->unsignedInteger('channel_id')->nullable();

            // Earnings
            $table->decimal('base_pay', 12, 2)->default(0);
            $table->decimal('overtime_pay', 12, 2)->default(0);
            $table->decimal('night_pay', 12, 2)->default(0);
            $table->decimal('holiday_pay', 12, 2)->default(0);
            $table->decimal('commute_allowance', 12, 2)->default(0);
            $table->decimal('other_allowance', 12, 2)->default(0);
            $table->decimal('gross_pay', 12, 2)->default(0);

            // Deductions
            $table->decimal('health_insurance', 12, 2)->default(0);
            $table->decimal('pension', 12, 2)->default(0);
            $table->decimal('employment_insurance', 12, 2)->default(0);
            $table->decimal('income_tax', 12, 2)->default(0);
            $table->decimal('resident_tax', 12, 2)->default(0);
            $table->decimal('other_deduction', 12, 2)->default(0);
            $table->decimal('total_deduction', 12, 2)->default(0);

            $table->decimal('net_pay', 12, 2)->default(0);

            // Time totals
            $table->unsignedInteger('regular_minutes')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);
            $table->unsignedInteger('night_minutes')->default(0);
            $table->unsignedInteger('holiday_minutes')->default(0);

            $table->enum('status', ['draft', 'finalized', 'paid'])->default('draft');
            $table->dateTime('finalized_at')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->json('calc_meta')->nullable();   // snapshot of rates used
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['staff_id', 'year_month']);
            $table->index('year_month');
            $table->index('status');

            $table->foreign('staff_id')
                ->references('id')->on('fujiba_staff')
                ->onDelete('restrict');

            $table->foreign('journal_entry_id')
                ->references('id')->on('fujiba_journal_entries')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fujiba_payroll_slips');
    }
};
