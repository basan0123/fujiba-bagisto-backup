<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Add recipient_type to notification_templates if missing.
        if (Schema::hasTable('notification_templates') && ! Schema::hasColumn('notification_templates', 'recipient_type')) {
            Schema::table('notification_templates', function (Blueprint $table) {
                $table->string('recipient_type', 20)->default('customer')->after('notification_channel');
            });
        }

        // 2) Drop the old (event, channel, language, channel_id) unique because
        //    we need recipient_type in the key — same row tuple can now exist
        //    for both customer and admin.
        if ($this->indexExists('notification_templates', 'fujiba_notif_unique_v2')) {
            Schema::table('notification_templates', function (Blueprint $table) {
                $table->dropUnique('fujiba_notif_unique_v2');
            });
        }
        if (! $this->indexExists('notification_templates', 'fujiba_notif_unique_v3')) {
            Schema::table('notification_templates', function (Blueprint $table) {
                $table->unique(
                    ['event', 'notification_channel', 'recipient_type', 'language', 'channel_id'],
                    'fujiba_notif_unique_v3'
                );
            });
        }

        // 3) New table: admin recipient PSIDs per channel.
        if (! Schema::hasTable('notification_admin_recipients')) {
            Schema::create('notification_admin_recipients', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('channel_id')->nullable();
                $table->string('page_id', 100);
                $table->string('psid', 64);
                $table->string('label', 100)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! $this->indexExists('notification_admin_recipients', 'fujiba_admin_recipient_unique')) {
            Schema::table('notification_admin_recipients', function (Blueprint $table) {
                $table->unique(['channel_id', 'page_id', 'psid'], 'fujiba_admin_recipient_unique');
            });
        }

        if (! $this->indexExists('notification_admin_recipients', 'fujiba_admin_recipient_lookup_idx')) {
            Schema::table('notification_admin_recipients', function (Blueprint $table) {
                $table->index(['channel_id', 'is_active'], 'fujiba_admin_recipient_lookup_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notification_admin_recipients')) {
            Schema::dropIfExists('notification_admin_recipients');
        }

        if ($this->indexExists('notification_templates', 'fujiba_notif_unique_v3')) {
            Schema::table('notification_templates', function (Blueprint $table) {
                $table->dropUnique('fujiba_notif_unique_v3');
            });
        }

        if (! $this->indexExists('notification_templates', 'fujiba_notif_unique_v2')) {
            Schema::table('notification_templates', function (Blueprint $table) {
                $table->unique(['event', 'notification_channel', 'language', 'channel_id'], 'fujiba_notif_unique_v2');
            });
        }

        if (Schema::hasTable('notification_templates') && Schema::hasColumn('notification_templates', 'recipient_type')) {
            Schema::table('notification_templates', function (Blueprint $table) {
                $table->dropColumn('recipient_type');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $database = DB::connection()->getDatabaseName();

        return (bool) DB::selectOne(
            'SELECT 1 FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, $table, $indexName]
        );
    }
};
