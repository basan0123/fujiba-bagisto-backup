<?php

// app/Console/Commands/BackfillPosUserChannelMap.php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User as CentralUser;
use App\Models\UserChannelMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Models\Channel;
use Webkul\Pos\Models\User as PosUser;

class BackfillPosUserChannelMap extends Command
{
    protected $signature = 'pos:backfill-channel-map {--dry-run : Log what would be inserted without writing to DB}';

    protected $description = 'Backfill user_channel_map for all existing POS users across all tenants';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('[DRY RUN] No changes will be written to the database.');
        }

        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->info('No tenants found. Nothing to backfill.');
            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);
                $this->processTenant($tenant, $dryRun);
            } catch (\Throwable $e) {
                $msg = "Tenant {$tenant->id}: failed to initialize — {$e->getMessage()}";
                $this->error($msg);
                Log::error('pos:backfill-channel-map ' . $msg);
            } finally {
                tenancy()->end();
            }
        }

        return self::SUCCESS;
    }

    private function processTenant(Tenant $tenant, bool $dryRun): void
    {
        $channel = Channel::first();

        if (! $channel) {
            $this->warn("Tenant {$tenant->id}: no channels found, skipping.");
            return;
        }

        $inventorySourceId = $channel->inventory_sources()->first()?->id;

        $mapped = 0;

        PosUser::chunk(100, function ($posUsers) use ($tenant, $channel, $inventorySourceId, $dryRun, &$mapped) {
            foreach ($posUsers as $posUser) {
                if (empty($posUser->email)) {
                    continue;
                }

                if ($dryRun) {
                    $this->line(
                        "[DRY RUN] Would map: email={$posUser->email}"
                        . " tenant={$tenant->id} channel={$channel->id}"
                    );
                    $mapped++;
                    continue;
                }

                $centralUser = CentralUser::firstOrCreate(
                    ['email' => $posUser->email],
                    [
                        'name'     => trim(($posUser->firstname ?? '') . ' ' . ($posUser->lastname ?? '')) ?: $posUser->email,
                        'password' => $posUser->getAttributes()['password'] ?? Hash::make(str()->random(32)),
                    ]
                );

                UserChannelMap::on('mysql')->updateOrInsert(
                    [
                        'user_id'    => $centralUser->id,
                        'channel_id' => $channel->id,
                    ],
                    [
                        'tenant_id'           => $tenant->id,
                        'inventory_source_id' => $inventorySourceId,
                        'is_active'           => true,
                        'updated_at'          => now(),
                        'created_at'          => DB::raw('IFNULL(created_at, NOW())'),
                    ]
                );

                $mapped++;
            }
        });

        $label = $dryRun ? '[DRY RUN] Would map' : 'Mapped';
        $this->info("Tenant {$tenant->id}: {$label} {$mapped} user(s).");
        Log::info("pos:backfill-channel-map Tenant {$tenant->id}: {$mapped} users mapped.", [
            'dry_run'    => $dryRun,
            'channel_id' => $channel->id,
        ]);
    }
}
