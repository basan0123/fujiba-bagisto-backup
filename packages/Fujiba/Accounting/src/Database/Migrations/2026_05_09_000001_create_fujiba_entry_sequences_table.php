<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-month entry-number sequence used by JournalPoster.
     * Single-row-per-month + SELECT … FOR UPDATE makes the increment race-safe.
     */
    public function up(): void
    {
        if (Schema::hasTable('fujiba_entry_sequences')) {
            return;
        }

        Schema::create('fujiba_entry_sequences', function (Blueprint $table) {
            $table->char('year_month', 7)->primary();   // "2026-05"
            $table->unsignedInteger('last_seq')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fujiba_entry_sequences');
    }
};
