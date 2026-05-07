<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BackfillStorefrontNew extends Command
{
    protected $signature = 'fujiba:backfill-storefront-new
        {--tenant= : Tenant UUID (required)}
        {--channel= : Channel code (required)}
        {--days=30 : Mark products created within last N days as new}
        {--dry-run : Show counts without writing}';

    protected $description = 'Set the new=1 attribute (PAV + product_flat) for products in <channel> created within the last N days. Idempotent.';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $channelCode = $this->option('channel');
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        if (! $tenantId || ! $channelCode || $days < 1) {
            $this->error('Required: --tenant=<uuid> --channel=<code> --days=<int>');
            return self::FAILURE;
        }

        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            $this->error("Tenant {$tenantId} not found.");
            return self::FAILURE;
        }
        tenancy()->initialize($tenant);

        $channel = DB::table('channels')->where('code', $channelCode)->first();
        if (! $channel) {
            $this->error("Channel '{$channelCode}' not found.");
            return self::FAILURE;
        }
        $attrNew = DB::table('attributes')->where('code', 'new')->first();
        if (! $attrNew) {
            $this->error("attribute 'new' not found.");
            return self::FAILURE;
        }

        $cutoff = Carbon::now()->subDays($days);
        $this->info("Tenant: {$tenantId}");
        $this->info('DB: '.DB::connection()->getDatabaseName());
        $this->info("Channel: {$channelCode}  attr.new id={$attrNew->id} value_per_channel={$attrNew->value_per_channel}");
        $this->info("Cutoff: products.created_at >= {$cutoff->toDateTimeString()} (last {$days} days)");
        $this->info($dryRun ? '[DRY RUN]' : '[APPLY]');

        // Eligible products: linked to this channel AND created on/after cutoff
        $eligible = DB::table('products as p')
            ->join('product_channels as pc', 'pc.product_id', '=', 'p.id')
            ->where('pc.channel_id', $channel->id)
            ->where('p.created_at', '>=', $cutoff)
            ->pluck('p.id')
            ->unique();

        $this->info('Eligible products: '.count($eligible));

        // Existing PAV rows for attribute_id=new on this channel scope (or null channel if !value_per_channel)
        $pavQuery = DB::table('product_attribute_values')->where('attribute_id', $attrNew->id)
            ->whereIn('product_id', $eligible);
        if ($attrNew->value_per_channel) {
            $pavQuery->where('channel', $channelCode);
        } else {
            $pavQuery->whereNull('channel');
        }
        $existingPav = $pavQuery->pluck('id', 'product_id');

        $needPavInsert = $eligible->diff($existingPav->keys())->values();
        $needPavUpdate = $existingPav->keys()->all();

        $needFlatUpdate = DB::table('product_flat')
            ->whereIn('product_id', $eligible)
            ->where('channel', $channelCode)
            ->where(function ($q) {
                $q->where('new', '!=', 1)->orWhereNull('new');
            })
            ->count();

        $this->table(['Action', 'Count'], [
            ["INSERT new=1 PAV (no row)", $needPavInsert->count()],
            ['UPDATE new PAV → 1 (existing)', count($needPavUpdate)],
            ['UPDATE product_flat.new → 1', $needFlatUpdate],
        ]);

        if ($dryRun) {
            $this->warn('[DRY RUN] No changes written.');
            return self::SUCCESS;
        }

        $insertedPav = 0; $updatedPav = 0; $updatedFlat = 0;

        DB::transaction(function () use (
            $needPavInsert, $needPavUpdate, $eligible,
            $attrNew, $channelCode, $channel,
            &$insertedPav, &$updatedPav, &$updatedFlat
        ) {
            foreach ($needPavInsert->chunk(500) as $chunk) {
                $rows = [];
                foreach ($chunk as $pid) {
                    $channelVal = $attrNew->value_per_channel ? $channelCode : null;
                    $rows[] = [
                        'product_id'    => $pid,
                        'attribute_id'  => $attrNew->id,
                        'channel'       => $channelVal,
                        'locale'        => null,
                        'boolean_value' => 1,
                        'unique_id'     => implode('|', array_filter([$channelVal, null, $pid, $attrNew->id])),
                    ];
                }
                DB::table('product_attribute_values')->insert($rows);
                $insertedPav += count($rows);
            }

            if (! empty($needPavUpdate)) {
                $updatedPav = DB::table('product_attribute_values')
                    ->whereIn('id', $needPavUpdate)
                    ->update(['boolean_value' => 1]);
            }

            $updatedFlat = DB::table('product_flat')
                ->whereIn('product_id', $eligible)
                ->where('channel', $channelCode)
                ->update(['new' => 1]);
        });

        $this->info("Inserted PAV rows:  {$insertedPav}");
        $this->info("Updated PAV rows:   {$updatedPav}");
        $this->info("Updated flat rows:  {$updatedFlat}");

        return self::SUCCESS;
    }
}
