<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_log')) {
            Schema::create('notification_log', function (Blueprint $table) {
                $table->id();
                $table->string('event', 64);
                $table->string('notification_channel', 32);
                $table->string('subject_type', 191)->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->unsignedInteger('customer_id')->nullable();
                $table->string('recipient', 255)->nullable();
                $table->string('status', 16)->default('pending');
                $table->text('error')->nullable();
                $table->json('payload')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasIndex('notification_log', 'notification_log_subject_idx')) {
            Schema::table('notification_log', function (Blueprint $table) {
                $table->index(['subject_type', 'subject_id'], 'notification_log_subject_idx');
            });
        }

        if (! Schema::hasIndex('notification_log', 'notification_log_status_idx')) {
            Schema::table('notification_log', function (Blueprint $table) {
                $table->index(['event', 'status'], 'notification_log_status_idx');
            });
        }

        if (! Schema::hasIndex('notification_log', 'notification_log_customer_idx')) {
            Schema::table('notification_log', function (Blueprint $table) {
                $table->index('customer_id', 'notification_log_customer_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_log');
    }
};
