<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Instagram cross-post state, tracked separately from the Facebook columns:
 * an Instagram failure never touches the (already live) Facebook post, and
 * instagram_media_id plays the same never-resend role facebook_post_id does.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('autoposter_queue', 'instagram_status')) {
            return;
        }

        Schema::table('autoposter_queue', function (Blueprint $table) {
            $table->string('instagram_status', 20)->nullable()->index();   // pending|published|failed
            $table->json('instagram_payload')->nullable();                  // exact media sent to Facebook
            $table->string('instagram_media_id', 100)->nullable();
            $table->text('instagram_permalink')->nullable();
            $table->unsignedTinyInteger('instagram_attempts')->default(0);
            $table->text('instagram_error')->nullable();
            $table->timestamp('instagram_published_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('autoposter_queue', function (Blueprint $table) {
            $table->dropColumn(['instagram_status', 'instagram_payload', 'instagram_media_id', 'instagram_permalink', 'instagram_attempts', 'instagram_error', 'instagram_published_at']);
        });
    }
};
