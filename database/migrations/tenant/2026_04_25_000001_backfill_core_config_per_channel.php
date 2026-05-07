<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill: for every existing core_config row with channel_code IS NULL that
 * belongs to a section now marked channel_based (see AppServiceProvider::
 * patchCoreConfigChannelIsolation), duplicate that row for each channel.
 *
 * Idempotent: re-runs skip rows where a per-channel row already exists.
 */
return new class extends Migration
{
    private array $channelPrefixes = [
        'general.content.',
        'catalog.products.',
        'catalog.rich_snippets.',
        'sales.order_settings.',
        'sales.checkout.',
        'pwa.settings.',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('core_config') || ! Schema::hasTable('channels')) {
            return;
        }

        $channels = DB::table('channels')->pluck('code')->filter()->values()->all();

        if (empty($channels)) {
            return;
        }

        $nullRows = DB::table('core_config')
            ->whereNull('channel_code')
            ->get();

        foreach ($nullRows as $row) {
            $code = (string) $row->code;

            $belongs = false;
            foreach ($this->channelPrefixes as $p) {
                if (str_starts_with($code, $p)) {
                    $belongs = true;
                    break;
                }
            }
            if (! $belongs) {
                continue;
            }

            foreach ($channels as $channelCode) {
                $exists = DB::table('core_config')
                    ->where('code', $code)
                    ->where('channel_code', $channelCode)
                    ->when($row->locale_code, fn ($q) => $q->where('locale_code', $row->locale_code))
                    ->when(! $row->locale_code, fn ($q) => $q->whereNull('locale_code'))
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('core_config')->insert([
                    'code'         => $code,
                    'value'        => $row->value,
                    'channel_code' => $channelCode,
                    'locale_code'  => $row->locale_code,
                    'created_at'   => $row->created_at ?? now(),
                    'updated_at'   => now(),
                ]);
            }

            // Remove the legacy global row to avoid read ambiguity.
            DB::table('core_config')->where('id', $row->id)->delete();
        }
    }

    public function down(): void
    {
        // Not reversible — per-channel rows are intentionally preserved.
    }
};
