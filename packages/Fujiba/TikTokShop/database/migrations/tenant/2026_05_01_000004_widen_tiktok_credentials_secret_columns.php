<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Widen tiktok_credentials.tiktok_app_secret from VARCHAR(100) to TEXT.
 *
 * The original Step 1 migration sized this column at 100 chars. Laravel's
 * `encrypted` cast wraps plaintext in a base64-encoded JSON envelope
 * ({iv, value, mac}) that runs ~250-400 chars even for short secrets.
 * 100 chars truncates the ciphertext, causing DecryptException on read.
 *
 * access_token and refresh_token were already TEXT in Step 1, so they're
 * unaffected.
 *
 * Idempotent: introspects information_schema before altering, so re-running
 * tenants:migrate on a tenant where the column is already TEXT is a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tiktok_credentials')) {
            return;
        }

        $current = collect(DB::select(
            "SELECT DATA_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'tiktok_credentials'
               AND COLUMN_NAME = 'tiktok_app_secret'"
        ))->first();

        if ($current && strtolower($current->DATA_TYPE) === 'varchar') {
            // Use raw ALTER — doctrine/dbal may not be installed and Schema::table
            // ->change() requires it. Raw SQL is safe here because the column
            // is being widened (no data loss, no type-coercion ambiguity).
            DB::statement('ALTER TABLE `tiktok_credentials` MODIFY `tiktok_app_secret` TEXT NOT NULL');
        }
    }

    public function down(): void
    {
        // No-op down. Narrowing back to VARCHAR(100) would re-introduce the
        // truncation bug; if a rollback is genuinely needed, write a fresh
        // forward migration.
    }
};
