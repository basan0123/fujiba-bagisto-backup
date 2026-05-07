<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_templates')) {
            return;
        }

        if (! Schema::hasColumn('notification_templates', 'channel_id')) {
            Schema::table('notification_templates', function (Blueprint $table) {
                $table->unsignedInteger('channel_id')->nullable()->after('language');
            });
        }

        // Drop old unique (event, notification_channel, language) so the new
        // 4-column unique can take over. Without dropping, the old index
        // forbids per-channel rows because (event, channel, lang) repeats.
        if ($this->indexExists('notification_templates', 'notification_templates_resolve_idx')) {
            Schema::table('notification_templates', function (Blueprint $table) {
                $table->dropUnique('notification_templates_resolve_idx');
            });
        }
        if ($this->indexExists('notification_templates', 'fujiba_notif_unique')) {
            Schema::table('notification_templates', function (Blueprint $table) {
                $table->dropUnique('fujiba_notif_unique');
            });
        }

        if (! $this->indexExists('notification_templates', 'fujiba_notif_unique_v2')) {
            Schema::table('notification_templates', function (Blueprint $table) {
                $table->unique(
                    ['event', 'notification_channel', 'language', 'channel_id'],
                    'fujiba_notif_unique_v2'
                );
            });
        }

        if (! $this->indexExists('notification_templates', 'fujiba_notif_channel_idx')) {
            Schema::table('notification_templates', function (Blueprint $table) {
                $table->index(['event', 'notification_channel', 'channel_id'], 'fujiba_notif_channel_idx');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('notification_templates')) {
            return;
        }

        if ($this->indexExists('notification_templates', 'fujiba_notif_channel_idx')) {
            Schema::table('notification_templates', function (Blueprint $table) {
                $table->dropIndex('fujiba_notif_channel_idx');
            });
        }

        if ($this->indexExists('notification_templates', 'fujiba_notif_unique_v2')) {
            Schema::table('notification_templates', function (Blueprint $table) {
                $table->dropUnique('fujiba_notif_unique_v2');
            });
        }

        // Restore the original 3-column unique (best-effort; might fail if
        // duplicates exist from the per-channel period).
        if (! $this->indexExists('notification_templates', 'fujiba_notif_unique')) {
            Schema::table('notification_templates', function (Blueprint $table) {
                $table->unique(['event', 'notification_channel', 'language'], 'fujiba_notif_unique');
            });
        }

        if (Schema::hasColumn('notification_templates', 'channel_id')) {
            Schema::table('notification_templates', function (Blueprint $table) {
                $table->dropColumn('channel_id');
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
