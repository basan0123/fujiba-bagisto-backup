<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * FUJIBA 2026-05-30: tear down the standalone storefront-visitor chat data layer.
 *
 * The original `pos_chat_*` tables (created by the now-deleted
 * 2026_05_26_100001_create_pos_chat_tables migration) backed a separate POS web
 * chat system — storefront widget + REST endpoints + a custom bot-flow builder.
 * That whole system has been removed. POS chat is now Messenger-only and reuses
 * the existing Fujiba\LiveChat / Chatpion Facebook Messenger stack, so these
 * tables are dead weight.
 *
 * Idempotent (hasTable guards) and ordered child-before-parent so the FK on
 * pos_chat_message → pos_chat_conversation drops cleanly. Runs through the
 * normal per-tenant migration pipeline.
 *
 * Down() is intentionally a no-op: we never want to recreate the dead schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('pos_chat_message');
        Schema::dropIfExists('pos_chat_conversation');
        Schema::dropIfExists('pos_bot_flow');
    }

    public function down(): void
    {
        // No-op: the removed chat system is not coming back.
    }
};
