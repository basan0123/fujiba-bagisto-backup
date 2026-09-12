<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The posting queue — one row per post, in any lifecycle state.
 *
 * Statuses: draft | scheduled | processing | published | failed | cancelled.
 *
 * Double-publish protection is the point of this table, so it is worth being
 * explicit about the three mechanisms, which guard different failure modes:
 *
 *  1. lock_token + locked_at — a worker claims a row with a conditional UPDATE
 *     (status='scheduled' -> 'processing' WHERE lock_token IS NULL). Only one
 *     worker's UPDATE can match, so concurrent runners cannot both claim it.
 *     locked_at lets a reaper release rows whose worker died mid-publish.
 *
 *  2. idempotency_key — UNIQUE, generated when the row is created, and sent to
 *     Graph as the client-side dedup handle. Survives a crash *between* the FB
 *     call succeeding and us recording facebook_post_id, which is the one
 *     window a lock alone cannot close.
 *
 *  3. facebook_post_id — once set, the publisher refuses to re-send, no matter
 *     what the status column says. Last line of defence against a manual status
 *     edit in the admin UI or a bad retry.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('autoposter_queue')) {
            Schema::create('autoposter_queue', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('channel_id');

                $table->string('status', 20)->default('draft');

                // multi_photo | single_image | carousel_ad (reserved, not built)
                $table->string('format', 30)->default('multi_photo');

                $table->text('caption')->nullable();
                $table->string('language_mode', 20)->nullable();
                $table->unsignedBigInteger('template_id')->nullable();

                // Snapshot of the caption inputs so an admin can see exactly
                // what produced this text, and regenerate reproducibly.
                $table->json('caption_meta')->nullable();

                $table->string('messenger_destination', 255)->nullable();
                $table->string('messenger_ref', 100)->nullable();

                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('published_at')->nullable();

                $table->string('facebook_post_id', 100)->nullable();
                $table->text('facebook_permalink')->nullable();

                // Raw Graph response/error kept verbatim for debugging; the
                // admin UI shows it on failed rows.
                $table->json('last_api_response')->nullable();
                $table->text('last_error')->nullable();
                $table->timestamp('last_error_at')->nullable();

                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamp('next_retry_at')->nullable();

                $table->string('lock_token', 64)->nullable();
                $table->timestamp('locked_at')->nullable();

                // auto | manual | bulk — so history can distinguish a scheduled
                // post from an admin's Post Now.
                $table->string('origin', 20)->default('auto');
                $table->unsignedBigInteger('created_by')->nullable();

                $table->string('idempotency_key', 64);

                $table->timestamps();

                $table->unique('idempotency_key', 'autoposter_queue_idem_uq');
                $table->index(['channel_id', 'status', 'scheduled_at'], 'autoposter_queue_dispatch_idx');
                $table->index('facebook_post_id', 'autoposter_queue_fbpost_idx');
                $table->index(['status', 'next_retry_at'], 'autoposter_queue_retry_idx');
            });
        }

        $fkExists = collect(DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'autoposter_queue'
              AND CONSTRAINT_NAME = 'autoposter_queue_channel_id_foreign'
        "))->isNotEmpty();

        if (! $fkExists) {
            Schema::table('autoposter_queue', function (Blueprint $table) {
                $table->foreign('channel_id', 'autoposter_queue_channel_id_foreign')
                    ->references('id')->on('channels')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        $fkExists = collect(DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'autoposter_queue'
              AND CONSTRAINT_NAME = 'autoposter_queue_channel_id_foreign'
        "))->isNotEmpty();

        if ($fkExists) {
            Schema::table('autoposter_queue', function (Blueprint $table) {
                $table->dropForeign('autoposter_queue_channel_id_foreign');
            });
        }

        Schema::dropIfExists('autoposter_queue');
    }
};
