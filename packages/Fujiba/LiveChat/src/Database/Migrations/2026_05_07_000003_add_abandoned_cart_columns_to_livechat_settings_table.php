<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 of abandoned-cart recovery: per-channel toggles + delay.
 *
 * Mirrors the auto_flow_* column pattern from migration 000002. The admin UI
 * for these knobs comes in Phase 3; for Phase 1 the columns are toggle-only,
 * default-disabled, and edited via direct SQL until the UI lands.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('livechat_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('livechat_settings', 'abandoned_cart_enabled')) {
                $table->boolean('abandoned_cart_enabled')
                    ->default(false)
                    ->after('auto_flow_grace_minutes');
            }
            if (! Schema::hasColumn('livechat_settings', 'abandoned_cart_delay_minutes')) {
                // Delay between cart.updated_at and the reminder send. 60 min default.
                $table->unsignedSmallInteger('abandoned_cart_delay_minutes')
                    ->default(60)
                    ->after('abandoned_cart_enabled');
            }
            if (! Schema::hasColumn('livechat_settings', 'abandoned_cart_message_en')) {
                $table->text('abandoned_cart_message_en')->nullable()
                    ->after('abandoned_cart_delay_minutes');
            }
            if (! Schema::hasColumn('livechat_settings', 'abandoned_cart_message_ja')) {
                $table->text('abandoned_cart_message_ja')->nullable()
                    ->after('abandoned_cart_message_en');
            }
            if (! Schema::hasColumn('livechat_settings', 'abandoned_cart_message_my')) {
                $table->text('abandoned_cart_message_my')->nullable()
                    ->after('abandoned_cart_message_ja');
            }
        });
    }

    public function down(): void
    {
        Schema::table('livechat_settings', function (Blueprint $table) {
            foreach ([
                'abandoned_cart_message_my',
                'abandoned_cart_message_ja',
                'abandoned_cart_message_en',
                'abandoned_cart_delay_minutes',
                'abandoned_cart_enabled',
            ] as $col) {
                if (Schema::hasColumn('livechat_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
