<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WS-J — engagement numbers read off owner screenshots
 * (fujiba:intel:competitor-metrics). Additive only: views/likes/comments/shares
 * and metrics_status already exist on fi_competitor_posts.
 */
return new class extends Migration
{
    private const COLUMNS = ['saves', 'cover_note', 'metrics_source_file', 'metrics_captured_at', 'metrics_notes'];

    public function up(): void
    {
        if (! Schema::hasTable('fi_competitor_posts')) {
            return;
        }

        Schema::table('fi_competitor_posts', function (Blueprint $t) {
            if (! Schema::hasColumn('fi_competitor_posts', 'saves')) {
                $t->unsignedBigInteger('saves')->nullable()->after('shares');
            }
            if (! Schema::hasColumn('fi_competitor_posts', 'cover_note')) {
                $t->string('cover_note', 255)->nullable()->after('saves');      // how the owner identified the post
            }
            if (! Schema::hasColumn('fi_competitor_posts', 'metrics_source_file')) {
                $t->string('metrics_source_file', 512)->nullable()->after('metrics_status');
            }
            if (! Schema::hasColumn('fi_competitor_posts', 'metrics_captured_at')) {
                $t->timestamp('metrics_captured_at')->nullable()->after('metrics_source_file');
            }
            if (! Schema::hasColumn('fi_competitor_posts', 'metrics_notes')) {
                $t->text('metrics_notes')->nullable()->after('metrics_captured_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fi_competitor_posts')) {
            return;
        }

        $present = array_values(array_filter(self::COLUMNS, fn ($c) => Schema::hasColumn('fi_competitor_posts', $c)));
        if ($present) {
            Schema::table('fi_competitor_posts', fn (Blueprint $t) => $t->dropColumn($present));
        }
    }
};
