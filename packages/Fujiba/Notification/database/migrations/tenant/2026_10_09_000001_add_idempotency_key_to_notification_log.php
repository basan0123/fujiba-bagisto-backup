<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One meaningful order event → at most one notification per recipient.
 *
 * The key is built by OrderNotificationMatrix (e.g.
 * "order:123:shipped:shipment:456:messenger:<psid>"). The UNIQUE index is the
 * real guarantee: if the same Laravel event fires twice — or two code paths
 * (cancel.after + update-status.after) describe the same change — the second
 * insert collides and nothing is queued. NULL for legacy rows and for
 * non-order notifications, which MySQL lets repeat freely.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_log') || Schema::hasColumn('notification_log', 'idempotency_key')) {
            return;
        }

        Schema::table('notification_log', function (Blueprint $table) {
            $table->string('idempotency_key', 191)->nullable()->after('recipient');
            $table->unique('idempotency_key', 'notification_log_idempotency_key_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('notification_log', 'idempotency_key')) {
            return;
        }

        Schema::table('notification_log', function (Blueprint $table) {
            $table->dropUnique('notification_log_idempotency_key_unique');
            $table->dropColumn('idempotency_key');
        });
    }
};
