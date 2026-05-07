<?php
namespace App\Console\Commands;

use App\Jobs\SslGenerateJob;
use App\Models\Tenant;
use App\Services\SuperAdmin\SslService;
use Illuminate\Console\Command;

class SslAutoRenew extends Command
{
    protected $signature = 'ssl:auto-renew {--days=30 : Renew if cert expires in fewer than N days}';
    protected $description = 'Refresh SSL status for all tenants and dispatch renewals for certs expiring within --days.';

    public function handle(SslService $ssl): int
    {
        $days = (int) $this->option('days');
        $renewed = 0;
        $checked = 0;

        Tenant::with('domains')->whereNull('deleted_at')->chunkById(100, function ($tenants) use ($ssl, &$checked, &$renewed, $days) {
            foreach ($tenants as $tenant) {
                $checked++;
                $ssl->refreshStatus($tenant);

                $expires = $tenant->refresh()->ssl_expires_at;
                if (! $expires) {
                    continue;
                }
                if (\Carbon\Carbon::parse($expires)->lt(now()->addDays($days))) {
                    $domain = $tenant->domains->first()?->domain;
                    if ($domain) {
                        SslGenerateJob::dispatch($tenant->id, $domain, $tenant->owner_email ?? 'admin@snssale.com', 'renew');
                        $renewed++;
                    }
                }
            }
        });

        $this->info("Checked {$checked} tenants; dispatched {$renewed} renewals.");
        return self::SUCCESS;
    }
}
