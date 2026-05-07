<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('auto_flow_dispatch_log')) {
            return;
        }

        Schema::create('auto_flow_dispatch_log', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->unsignedInteger('channel_id');
            $table->string('fb_bot_id');
            $table->string('subscriber_id', 64);
            $table->string('postback_id', 64);
            $table->string('fb_thread_id', 128)->nullable();
            $table->timestamp('candidate_seen_at');
            $table->timestamp('dispatched_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'skipped_not_new'])
                  ->default('pending');
            $table->unsignedSmallInteger('steps_total')->nullable();
            $table->unsignedSmallInteger('steps_sent')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'channel_id', 'subscriber_id', 'postback_id'],
                'uniq_tenant_channel_subscriber_flow'
            );
            $table->index(['tenant_id', 'channel_id', 'dispatched_at'], 'idx_tenant_channel_dispatched');
            $table->index('subscriber_id', 'idx_subscriber');
            $table->index(['status', 'candidate_seen_at'], 'idx_status_pending');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_flow_dispatch_log');
    }
};
