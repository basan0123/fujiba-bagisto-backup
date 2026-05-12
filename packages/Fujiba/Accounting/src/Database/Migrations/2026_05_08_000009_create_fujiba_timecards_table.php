<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fujiba_timecards')) {
            return;
        }

        Schema::create('fujiba_timecards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->date('work_date');
            $table->dateTime('clock_in')->nullable();
            $table->dateTime('clock_out')->nullable();
            $table->dateTime('break_start')->nullable();
            $table->dateTime('break_end')->nullable();
            $table->unsignedInteger('break_minutes')->default(0);
            // computed work minutes (regular vs overtime split happens in payroll)
            $table->unsignedInteger('work_minutes')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);
            $table->unsignedInteger('night_minutes')->default(0);   // 22:00–05:00
            $table->unsignedInteger('holiday_minutes')->default(0);
            $table->unsignedInteger('channel_id')->nullable();      // shop worked at
            $table->enum('status', ['open', 'closed', 'edited', 'approved'])->default('open');
            $table->text('notes')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['staff_id', 'work_date']);
            $table->index('work_date');
            $table->index('channel_id');

            $table->foreign('staff_id')
                ->references('id')->on('fujiba_staff')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fujiba_timecards');
    }
};
