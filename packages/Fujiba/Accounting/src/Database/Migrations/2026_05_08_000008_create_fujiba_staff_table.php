<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fujiba_staff')) {
            return;
        }

        Schema::create('fujiba_staff', function (Blueprint $table) {
            $table->id();
            $table->string('staff_code', 16)->unique();
            $table->string('name_ja', 128);
            $table->string('name_kana', 128)->nullable();
            $table->string('name_en', 128)->nullable();
            $table->string('email', 128)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('qr_token', 64)->unique();          // for timecard kiosk scanner
            $table->unsignedInteger('channel_id')->nullable(); // primary work location
            $table->enum('employment_type', ['full_time', 'part_time', 'arubaito', 'contractor', 'officer'])
                  ->default('part_time');
            $table->date('hire_date')->nullable();
            $table->date('termination_date')->nullable();
            // 源泉徴収区分: A (甲) / B (乙) / C (丙) — controls withholding bracket
            $table->enum('withholding_class', ['A', 'B', 'C'])->default('A');
            $table->unsignedTinyInteger('dependents_count')->default(0);
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->decimal('monthly_salary', 12, 2)->nullable();
            $table->boolean('enrolled_health_insurance')->default(false);
            $table->boolean('enrolled_pension')->default(false);
            $table->boolean('enrolled_employment_insurance')->default(false);
            $table->string('bank_name', 64)->nullable();
            $table->string('bank_branch', 64)->nullable();
            $table->string('bank_account_type', 16)->nullable();
            $table->string('bank_account_number', 32)->nullable();
            $table->string('bank_account_holder', 128)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('channel_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fujiba_staff');
    }
};
