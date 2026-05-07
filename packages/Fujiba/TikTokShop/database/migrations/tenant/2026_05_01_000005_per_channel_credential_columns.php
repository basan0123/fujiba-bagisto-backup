<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 3 micro-step: per-channel TikTok credentials.
 *
 * Sprint 2 stored tiktok_app_key/secret in the credentials row but the OAuth
 * flow read them from config('fujiba-tiktok')/.env. To support multiple
 * tenants connecting to different TikTok developer apps, the row must be
 * the source of truth. That requires:
 *
 *   1. tiktok_app_key  — was NOT NULL, now NULLABLE (admin enters before OAuth)
 *   2. tiktok_app_secret — was NOT NULL TEXT, now NULLABLE TEXT (same)
 *   3. redirect_uri (new) — TEXT NULL, per-channel OAuth callback URL
 *
 * Idempotent: introspects information_schema before each ALTER, so re-running
 * tenants:migrate after a partial run is safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tiktok_credentials')) {
            return;
        }

        // 1. tiktok_app_key — make NULLABLE
        $appKey = collect(DB::select(
            "SELECT IS_NULLABLE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'tiktok_credentials'
               AND COLUMN_NAME = 'tiktok_app_key'"
        ))->first();
        if ($appKey && $appKey->IS_NULLABLE === 'NO') {
            DB::statement('ALTER TABLE `tiktok_credentials` MODIFY `tiktok_app_key` VARCHAR(100) NULL');
        }

        // 2. tiktok_app_secret — make NULLABLE TEXT
        $appSecret = collect(DB::select(
            "SELECT IS_NULLABLE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'tiktok_credentials'
               AND COLUMN_NAME = 'tiktok_app_secret'"
        ))->first();
        if ($appSecret && $appSecret->IS_NULLABLE === 'NO') {
            DB::statement('ALTER TABLE `tiktok_credentials` MODIFY `tiktok_app_secret` TEXT NULL');
        }

        // 3. redirect_uri — add column if missing
        if (! Schema::hasColumn('tiktok_credentials', 'redirect_uri')) {
            Schema::table('tiktok_credentials', function (Blueprint $table) {
                $table->text('redirect_uri')->nullable()->after('tiktok_app_secret');
            });
        }
    }

    public function down(): void
    {
        // No-op down. Reintroducing NOT NULL on tiktok_app_key / tiktok_app_secret
        // would break the per-channel UX. Drop the redirect_uri column only if
        // an explicit rollback is needed in the future.
    }
};
