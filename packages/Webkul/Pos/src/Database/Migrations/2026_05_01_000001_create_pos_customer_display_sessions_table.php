<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pos_customer_display_sessions')) {
            return;
        }

        Schema::create('pos_customer_display_sessions', function (Blueprint $table) {
            $table->id();

            $table->string('tenant_id', 36)->index();

            $table->unsignedInteger('agent_id');
            $table->unsignedInteger('outlet_id');

            $table->string('terminal_uuid', 64);
            $table->string('pairing_token', 43)->unique();

            $table->json('last_payload')->nullable();
            $table->timestamp('last_payload_at')->nullable();

            $table->string('paired_device_label', 120)->nullable();
            $table->string('paired_device_ua', 500)->nullable();
            $table->ipAddress('paired_device_ip')->nullable();
            $table->timestamp('last_seen_at')->nullable();

            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason', 60)->nullable();

            $table->timestamps();

            $table->foreign('agent_id')->references('id')->on('pos_users')->onDelete('cascade');
            $table->foreign('outlet_id')->references('id')->on('pos_outlets')->onDelete('cascade');

            $table->index(['agent_id', 'revoked_at']);
            $table->index(['tenant_id', 'pairing_token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_customer_display_sessions');
    }
};
