<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per (cart, step) reminder dispatch.
 *
 * UNIQUE(tenant_id, cart_id, step) is the idempotency key — if the dispatcher
 * runs twice in the same window, the second insert errors with 1062 and the
 * loop skips. Mirrors auto_flow_dispatch_log's pattern exactly.
 *
 * Phase 1 only writes step=1. Phase 4 will write step=2 and step=3.
 *
 * Lives on central DB (mysql connection) because livechat_settings is central
 * and one log table for all tenants is simpler than tenant-scoped logs.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('abandoned_cart_dispatch_log')) {
            return;
        }

        Schema::create('abandoned_cart_dispatch_log', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->unsignedInteger('channel_id');
            $table->string('fb_bot_id');
            $table->unsignedInteger('cart_id');
            $table->unsignedInteger('customer_id');
            $table->string('subscriber_id', 64);
            $table->unsignedTinyInteger('step')->default(1);
            $table->timestamp('candidate_seen_at');
            $table->timestamp('dispatched_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'skipped_no_token', 'skipped_outside_24h'])
                  ->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'cart_id', 'step'],
                'uniq_tenant_cart_step'
            );
            $table->index(['tenant_id', 'channel_id', 'dispatched_at'], 'idx_acdl_tenant_channel_dispatched');
            $table->index('subscriber_id', 'idx_acdl_subscriber');
            $table->index(['status', 'candidate_seen_at'], 'idx_acdl_status_pending');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abandoned_cart_dispatch_log');
    }
};
