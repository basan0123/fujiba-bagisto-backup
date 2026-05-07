<?php
namespace App\Jobs;

use App\Models\Tenant;
use App\Services\SuperAdmin\ProvisioningStatus;
use App\Services\SuperAdmin\TenantCloner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CloneTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800; // 30 min — large catalogs take time
    public int $tries = 1;       // do not auto-retry; failed runs leave a half-cloned DB to inspect

    public function __construct(
        public int    $jobId,
        public string $newTenantId,
        public array  $payload,
    ) {}

    public function handle(TenantCloner $cloner): void
    {
        $status = ProvisioningStatus::for($this->jobId);
        $status->began();
        $status->tenantId($this->newTenantId);

        try {
            // Step 1 — create tenant row + DB
            $status->start('create_db', 'Creating tenant row + database');
            $tenant = $this->createTenantRow();
            $status->done('create_db', "Tenant {$tenant->id} created, DB tenant_{$tenant->id}");

            // Step 2 — populate the new tenant DB with the master template
            // schema, then run tenants:migrate to top up. The master template
            // carries every Bagisto package migration that was applied at
            // installation time; tenants:migrate alone is incomplete because
            // tenancy.php's --path list only includes a subset of package
            // migration paths (this is the same tradeoff the original
            // mysqldump-based create flow leans on).
            $status->start('migrate', 'Cloning master template schema + running tenants:migrate');
            $cloner->seedFromMasterTemplate($tenant);
            Artisan::call('tenants:migrate', [
                '--tenants' => [$tenant->id],
                '--force'   => true,
            ]);
            $status->done('migrate', 'Master template schema cloned and migrations applied');

            // Step 3 — wipe seeded default channel/categories/attributes
            $status->start('wipe_seed', 'Wiping seeded default channel/categories/attributes');
            $cloner->wipeSeed($tenant);
            $status->done('wipe_seed', 'Seed wiped');

            // Step 4 — copy catalog
            $status->start('copy_catalog', 'Copying catalog (this can take a while)');
            $summary = $cloner->copyCatalog(
                sourceTenantId: $this->payload['source_tenant_id'],
                targetTenant: $tenant,
                channelCodes: $this->payload['source_channels'],
            );
            $status->done('copy_catalog', $summary);

            // Step 5 — rsync product image directories (optional)
            $status->start('rsync_images', 'Copying product images');
            if (! empty($this->payload['copy_images'])) {
                $msg = $cloner->rsyncImages($this->payload['source_tenant_id'], $tenant->id);
                $status->done('rsync_images', $msg);
            } else {
                $status->done('rsync_images', 'Skipped (copy_images=false)');
            }

            // Step 6 — create admin user
            $status->start('create_admin', 'Creating admin user');
            $cloner->createAdmin(
                $tenant,
                $this->payload['admin_email'],
                $this->payload['admin_password'],
            );
            $status->done('create_admin', "Admin {$this->payload['admin_email']} created");

            // Step 7 — attach domain row
            $status->start('attach_domain', 'Attaching domain');
            $tenant->domains()->firstOrCreate(['domain' => $this->payload['domain']]);
            $status->done('attach_domain', $this->payload['domain']);

            // Step 8 — finalize
            $status->start('finalize', 'Finalizing');
            $loginUrl = 'http://' . $this->payload['domain'] . '/admin/login';
            $tenant->update([
                'plan_id'     => $this->payload['plan_id'],
                'owner_email' => $this->payload['owner_email'],
                'name'        => $this->payload['name'],
            ]);
            $status->done('finalize', $loginUrl);
            $status->finish($loginUrl);

        } catch (Throwable $e) {
            Log::error('[CloneTenantJob] failed', [
                'job_id'   => $this->jobId,
                'tenant'   => $this->newTenantId,
                'message'  => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);
            $current = $this->currentRunningStep($status->job()->steps ?? []);
            $status->fail($current ?? 'finalize', $e->getMessage());
            throw $e;
        }
    }

    private function createTenantRow(): Tenant
    {
        return Tenant::create([
            'id'          => $this->newTenantId,
            'name'        => $this->payload['name'],
            'email'       => $this->payload['owner_email'],
            'plan'        => 'cloned',
            'plan_id'     => $this->payload['plan_id'] ?? null,
            'status'      => true,
            'owner_email' => $this->payload['owner_email'],
        ]);
    }

    private function currentRunningStep(array $steps): ?string
    {
        foreach ($steps as $key => $s) {
            if (($s['status'] ?? null) === 'running') {
                return $key;
            }
        }
        // Otherwise the next pending step.
        foreach ($steps as $key => $s) {
            if (($s['status'] ?? null) === 'pending') {
                return $key;
            }
        }
        return null;
    }
}
