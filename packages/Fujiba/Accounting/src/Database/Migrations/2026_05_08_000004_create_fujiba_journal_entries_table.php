<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fujiba_journal_entries')) {
            return;
        }

        Schema::create('fujiba_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fiscal_period_id');
            $table->date('entry_date');
            // NULL = corporate-level (consolidated). 1 = kimono, 2 = mmfood.
            $table->unsignedInteger('channel_id')->nullable();
            // posted by which admin user
            $table->unsignedInteger('posted_by')->nullable();
            // bagisto.order, pos.order, payroll, manual, receipt, opening_balance, closing
            $table->string('source_type', 32)->default('manual');
            $table->unsignedBigInteger('source_id')->nullable();
            // Idempotency for cron-driven posters; UNIQUE so re-runs don't duplicate.
            $table->string('source_idempotency_key', 128)->nullable()->unique();
            $table->string('memo', 255)->nullable();
            $table->string('reference_no', 64)->nullable();   // arbitrary doc number
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');
            $table->dateTime('posted_at')->nullable();
            $table->unsignedBigInteger('reversed_by_id')->nullable();
            $table->timestamps();

            $table->index('entry_date');
            $table->index('channel_id');
            $table->index('source_type');
            $table->index(['source_type', 'source_id']);
            $table->index('status');

            $table->foreign('fiscal_period_id')
                ->references('id')->on('fujiba_fiscal_periods')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fujiba_journal_entries');
    }
};
