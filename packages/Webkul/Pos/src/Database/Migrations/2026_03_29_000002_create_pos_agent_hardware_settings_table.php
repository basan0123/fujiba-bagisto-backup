<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-agent hardware preferences.
 *
 * Stored in the tenant database (one row per POS agent) so different
 * cashier stations on the same tenant can have different hardware.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pos_agent_hardware_settings')) {
            return;
        }

        Schema::create('pos_agent_hardware_settings', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('agent_id')->unique()
                  ->comment('FK to pos_users.id — one hardware profile per agent.');

            $table->enum('paper_size', ['80mm', '58mm'])
                  ->default('80mm')
                  ->comment('Thermal receipt roll width. 80mm ≈ 48 chars/line, 58mm ≈ 32 chars/line.');

            $table->string('default_printer', 255)
                  ->nullable()
                  ->comment('Printer name as reported by CUPS / OS print subsystem.');

            $table->string('default_scanner', 255)
                  ->nullable()
                  ->default('keyboard-wedge')
                  ->comment('Scanner device ID or "keyboard-wedge" for USB keyboard-mode scanners.');

            $table->json('extra')
                  ->nullable()
                  ->comment('Reserved for future per-agent hardware options.');

            $table->timestamps();

            $table->foreign('agent_id')
                  ->references('id')
                  ->on('pos_users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_agent_hardware_settings');
    }
};
