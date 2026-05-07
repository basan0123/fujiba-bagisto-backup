<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TenantStorageSetup extends Command
{
    protected $signature = 'tenant:storage-setup {tenant_id?} {--all}';
    protected $description = 'Create storage directories for tenants under tenants/{channel_id}/{uuid}/';

    public function handle()
    {
        $tenants = $this->option('all')
            ? Tenant::all()
            : [Tenant::find($this->argument('tenant_id'))];

        foreach ($tenants as $tenant) {
            // Resolve default channel_id from the tenant DB
            try {
                tenancy()->initialize($tenant);
                $channelId = (int) (DB::table('channels')->orderBy('id')->value('id') ?? 1);
                tenancy()->end();
            } catch (\Throwable) {
                tenancy()->end();
                $channelId = 1;
            }

            $base = storage_path("app/public/tenants/{$channelId}/{$tenant->id}");

            foreach (['', '/product', '/product/thumbnail', '/category', '/velocity', '/theme'] as $sub) {
                $path = $base . $sub;
                if (! is_dir($path)) {
                    mkdir($path, 0775, true);
                    $this->info("Created: {$path}");
                }
            }

            @chown($base, 'www-data');
            shell_exec("chown -R www-data:www-data {$base}");
            $this->info("Storage ready for tenant: {$tenant->id}  (channel {$channelId})");
        }
    }
}
