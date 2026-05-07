<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-agent Training Mode.
 *
 * Lets a senior staff toggle a junior cashier into a sandbox where:
 *  - training_mode=1 → checkout skips order persistence (no sale row,
 *    no inventory decrement) but still prints if training_print_receipt=1.
 *  - training_print_receipt: independent toggle so a trainee can practice
 *    with the printer or without.
 *
 * Stored on pos_users (per-agent) so a cashier in training keeps the mode
 * across devices. Both default to 0 — production behaviour is unchanged
 * for existing agents.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_users', function (Blueprint $table) {
            if (! Schema::hasColumn('pos_users', 'training_mode')) {
                $table->boolean('training_mode')
                    ->default(0)
                    ->after('status')
                    ->comment('1 = sandbox mode: orders not saved, stock not reduced.');
            }

            if (! Schema::hasColumn('pos_users', 'training_print_receipt')) {
                $table->boolean('training_print_receipt')
                    ->default(0)
                    ->after('training_mode')
                    ->comment('Allow receipt print while in training_mode.');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pos_users', function (Blueprint $table) {
            if (Schema::hasColumn('pos_users', 'training_print_receipt')) {
                $table->dropColumn('training_print_receipt');
            }

            if (Schema::hasColumn('pos_users', 'training_mode')) {
                $table->dropColumn('training_mode');
            }
        });
    }
};
