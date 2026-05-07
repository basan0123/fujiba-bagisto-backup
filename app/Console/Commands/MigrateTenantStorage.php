<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateTenantStorage extends Command
{
    protected $signature = 'tenants:migrate-storage
                            {--dry-run : Show what would be moved without moving anything}';

    protected $description = 'Move tenant storage from tenants/{uuid}/ to tenants/{channel_id}/{uuid}/';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no files will be moved.');
        }

        foreach (Tenant::all() as $tenant) {
            $tenantId = $tenant->getTenantKey();

            // Resolve default channel_id from the tenant DB
            try {
                tenancy()->initialize($tenant);
                $channelId = (int) (DB::table('channels')->orderBy('id')->value('id') ?? 1);
                tenancy()->end();
            } catch (\Throwable $e) {
                tenancy()->end();
                $this->error("  [{$tenantId}] Could not connect: " . $e->getMessage());
                continue;
            }

            $oldRoot = storage_path("app/public/tenants/{$tenantId}");
            $newRoot = storage_path("app/public/tenants/{$channelId}/{$tenantId}");

            if (! is_dir($oldRoot)) {
                $this->line("  [{$tenantId}] Old path does not exist, skipping.");
                continue;
            }

            if (is_dir($newRoot) && $this->directoryHasFiles($newRoot)) {
                $this->warn("  [{$tenantId}] New path already has files — skipping to avoid overwrite.");
                continue;
            }

            $this->info("  [{$tenantId}] ch={$channelId}  {$oldRoot} → {$newRoot}");

            if (! $dryRun) {
                // Ensure parent directory exists
                $parentDir = storage_path("app/public/tenants/{$channelId}");
                if (! is_dir($parentDir)) {
                    mkdir($parentDir, 0775, true);
                }

                if (! rename($oldRoot, $newRoot)) {
                    $this->error("  [{$tenantId}] rename() failed — check permissions.");
                } else {
                    $this->info("  [{$tenantId}] Moved successfully.");
                }
            }
        }

        $this->info('Done.');

        return self::SUCCESS;
    }

    protected function directoryHasFiles(string $dir): bool
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                return true;
            }
        }
        return false;
    }
}
