<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillStorefrontVisibility extends Command
{
    protected $signature = 'fujiba:backfill-storefront-visibility
        {--tenant= : Tenant UUID (required)}
        {--channel= : Channel code, e.g. mmfood (required)}
        {--dry-run : Show counts without writing}';

    protected $description = 'Insert missing visible_individually=1 and status=1 product_attribute_values rows for products linked to a channel. Idempotent: only inserts where the row is absent.';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $channelCode = $this->option('channel');
        $dryRun = $this->option('dry-run');

        if (! $tenantId || ! $channelCode) {
            $this->error('Both --tenant=<uuid> and --channel=<code> are required.');
            return self::FAILURE;
        }

        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            $this->error("Tenant {$tenantId} not found.");
            return self::FAILURE;
        }

        tenancy()->initialize($tenant);

        $this->info("Tenant: {$tenantId}");
        $this->info('DB: '.DB::connection()->getDatabaseName());
        $this->info("Channel: {$channelCode}");
        $this->info($dryRun ? '[DRY RUN]' : '[APPLY]');

        $channel = DB::table('channels')->where('code', $channelCode)->first();
        if (! $channel) {
            $this->error("Channel '{$channelCode}' not found in tenant DB.");
            return self::FAILURE;
        }
        $channelId = $channel->id;

        $attrVisible = DB::table('attributes')->where('code', 'visible_individually')->first();
        $attrStatus  = DB::table('attributes')->where('code', 'status')->first();
        if (! $attrVisible || ! $attrStatus) {
            $this->error('Required attributes missing.');
            return self::FAILURE;
        }

        $linked = DB::table('product_channels')
            ->where('channel_id', $channelId)
            ->pluck('product_id');

        $this->info('Linked products: '.count($linked));

        // visible_individually: per-product (no channel/locale)
        $haveVisible = DB::table('product_attribute_values')
            ->where('attribute_id', $attrVisible->id)
            ->whereIn('product_id', $linked)
            ->pluck('product_id')
            ->unique();

        $needVisibleInsert = $linked->diff($haveVisible)->values();

        // visible_individually rows that exist but boolean_value != 1
        $haveVisibleZero = DB::table('product_attribute_values')
            ->where('attribute_id', $attrVisible->id)
            ->whereIn('product_id', $linked)
            ->where(function ($q) {
                $q->where('boolean_value', '!=', 1)->orWhereNull('boolean_value');
            })
            ->pluck('id', 'product_id');

        // status: per-channel — need a row with channel=<channelCode>
        $haveStatusForChannel = DB::table('product_attribute_values')
            ->where('attribute_id', $attrStatus->id)
            ->where('channel', $channelCode)
            ->whereIn('product_id', $linked)
            ->pluck('product_id')
            ->unique();

        $needStatusInsert = $linked->diff($haveStatusForChannel)->values();

        // status rows that exist for this channel but boolean_value != 1
        $haveStatusZeroForChannel = DB::table('product_attribute_values')
            ->where('attribute_id', $attrStatus->id)
            ->where('channel', $channelCode)
            ->whereIn('product_id', $linked)
            ->where(function ($q) {
                $q->where('boolean_value', '!=', 1)->orWhereNull('boolean_value');
            })
            ->pluck('id', 'product_id');

        $this->table(
            ['Action', 'Count'],
            [
                ['INSERT visible_individually=1 (no row exists)', $needVisibleInsert->count()],
                ['UPDATE visible_individually 0/NULL → 1', $haveVisibleZero->count()],
                ["INSERT status=1 (channel={$channelCode}, no row)", $needStatusInsert->count()],
                ["UPDATE status 0/NULL → 1 (channel={$channelCode})", $haveStatusZeroForChannel->count()],
            ]
        );

        if ($dryRun) {
            $this->warn('[DRY RUN] No changes written.');
            return self::SUCCESS;
        }

        $now = now();
        $insertedV = 0; $updatedV = 0; $insertedS = 0; $updatedS = 0;

        DB::transaction(function () use (
            $needVisibleInsert, $haveVisibleZero,
            $needStatusInsert, $haveStatusZeroForChannel,
            $attrVisible, $attrStatus, $channelCode, $now,
            &$insertedV, &$updatedV, &$insertedS, &$updatedS
        ) {
            // INSERT visible_individually
            foreach ($needVisibleInsert->chunk(500) as $chunk) {
                $rows = [];
                foreach ($chunk as $pid) {
                    $rows[] = [
                        'product_id'    => $pid,
                        'attribute_id'  => $attrVisible->id,
                        'channel'       => null,
                        'locale'        => null,
                        'boolean_value' => 1,
                        'unique_id'     => implode('|', array_filter([null, null, $pid, $attrVisible->id])),
                    ];
                }
                DB::table('product_attribute_values')->insert($rows);
                $insertedV += count($rows);
            }

            // UPDATE visible_individually 0/NULL → 1
            if ($haveVisibleZero->isNotEmpty()) {
                $updatedV = DB::table('product_attribute_values')
                    ->whereIn('id', $haveVisibleZero->values())
                    ->update(['boolean_value' => 1]);
            }

            // INSERT status (per channel)
            foreach ($needStatusInsert->chunk(500) as $chunk) {
                $rows = [];
                foreach ($chunk as $pid) {
                    $rows[] = [
                        'product_id'    => $pid,
                        'attribute_id'  => $attrStatus->id,
                        'channel'       => $channelCode,
                        'locale'        => null,
                        'boolean_value' => 1,
                        'unique_id'     => implode('|', array_filter([$channelCode, null, $pid, $attrStatus->id])),
                    ];
                }
                DB::table('product_attribute_values')->insert($rows);
                $insertedS += count($rows);
            }

            // UPDATE status 0/NULL → 1 (channel-scoped)
            if ($haveStatusZeroForChannel->isNotEmpty()) {
                $updatedS = DB::table('product_attribute_values')
                    ->whereIn('id', $haveStatusZeroForChannel->values())
                    ->update(['boolean_value' => 1]);
            }
        });

        $this->info("Inserted visible_individually rows: {$insertedV}");
        $this->info("Updated visible_individually rows:  {$updatedV}");
        $this->info("Inserted status rows:               {$insertedS}");
        $this->info("Updated status rows:                {$updatedS}");

        return self::SUCCESS;
    }
}
