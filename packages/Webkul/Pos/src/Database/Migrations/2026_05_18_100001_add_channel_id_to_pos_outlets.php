<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add channel_id to pos_outlets so POS-side queries (qualified invoices,
 * cashier reports, customer-display roster) can scope by channel without
 * relying on the lower(outlet.name) = channels.code naming convention.
 *
 * Backfill: name-match first (e.g. "MMFOOD" → "mmfood"). Anything left
 * unmatched falls back to channel id 1 (default storefront).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pos_outlets', 'channel_id')) {
            return;
        }

        Schema::table('pos_outlets', function (Blueprint $table) {
            $table->unsignedInteger('channel_id')->nullable()->after('inventory_source_id');
        });

        $defaultChannelId = (int) (DB::table('channels')->where('code', 'default')->value('id') ?: 1);

        foreach (DB::table('pos_outlets')->get(['id', 'name']) as $outlet) {
            $matchedId = DB::table('channels')
                ->whereRaw('LOWER(code) = ?', [strtolower((string) $outlet->name)])
                ->value('id');
            DB::table('pos_outlets')
                ->where('id', $outlet->id)
                ->update(['channel_id' => $matchedId ?: $defaultChannelId]);
        }

        Schema::table('pos_outlets', function (Blueprint $table) {
            $table->unsignedInteger('channel_id')->nullable(false)->default(1)->change();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('pos_outlets', 'channel_id')) {
            Schema::table('pos_outlets', function (Blueprint $table) {
                $table->dropColumn('channel_id');
            });
        }
    }
};
