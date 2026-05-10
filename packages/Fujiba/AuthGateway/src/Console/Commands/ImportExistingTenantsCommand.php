<?php

namespace Fujiba\AuthGateway\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Import pos_users from existing tenants into the central gateway tables so
 * legacy staff can sign in via snssale.com instead of per-tenant REST login.
 *
 * Idempotent — safe to re-run. Defaults to staging (tenant1, tenant2) only.
 * Production tenants require --allow-prod and a confirmation prompt.
 */
class ImportExistingTenantsCommand extends Command
{
    protected $signature = 'gateway:import-existing-tenants
                            {--tenant=*    : Tenant ID or domain to import (repeatable). Default: staging tenants only.}
                            {--allow-prod  : Required to operate on a production tenant.}
                            {--dry-run     : Show what would be imported without writing.}';

    protected $description = 'Backfill gateway_users / gateway_user_tenants / gateway_tenants from existing tenant pos_users tables.';

    /**
     * Tenant UUIDs that are LIVE production. The command will refuse to touch
     * these unless --allow-prod is passed.
     */
    private const PRODUCTION_TENANT_UUIDS = [
        '1bd1181f-fcd5-4238-a12d-a207d6ea4006', // Fujiba
        'e43ccde7-9eac-4dbf-9ea4-ba8fb31a91b8', // A6 Shop
    ];

    /**
     * Default staging targets if --tenant is not specified.
     */
    private const DEFAULT_STAGING_TENANT_UUIDS = [
        '7016ca8c-7b3c-4a61-ae3f-65626dd85fb4', // Tenant One
        '855c3286-41a6-468c-be3b-3436b1f9c504', // Tenant Two
    ];

    public function handle(): int
    {
        $dryRun  = (bool) $this->option('dry-run');
        $allowProd = (bool) $this->option('allow-prod');
        $requested = $this->option('tenant');

        $targets = empty($requested)
            ? self::DEFAULT_STAGING_TENANT_UUIDS
            : (array) $requested;

        $this->info('AuthGateway tenant import');
        $this->info('Mode: ' . ($dryRun ? 'DRY RUN' : 'WRITE'));
        $this->info('Targets: ' . implode(', ', $targets));
        $this->newLine();

        $totals = ['users_inserted' => 0, 'users_skipped' => 0, 'bridges_inserted' => 0, 'tenant_rows_inserted' => 0];

        foreach ($targets as $key) {
            $tenant = $this->resolveTenant($key);
            if (! $tenant) {
                $this->warn("  - {$key}: not found, skipping");
                continue;
            }

            $isProd = in_array($tenant->id, self::PRODUCTION_TENANT_UUIDS, true);
            if ($isProd && ! $allowProd) {
                $this->error("  - {$tenant->id} ({$tenant->name}): PRODUCTION tenant; pass --allow-prod to operate on this");
                continue;
            }
            if ($isProd) {
                if (! $this->confirm("Are you sure you want to import production tenant '{$tenant->name}' ({$tenant->id})?", false)) {
                    $this->warn('  Skipped by user.');
                    continue;
                }
            }

            $this->line("Tenant {$tenant->id} ({$tenant->name})");

            // pos_users live inside the tenant DB. Bootstrap into it.
            $posUsers = $tenant->run(function () {
                return DB::table('pos_users')->select('id', 'email', 'firstname', 'lastname', 'role', 'status')->get();
            });

            if ($posUsers->isEmpty()) {
                $this->warn('  no pos_users in this tenant, skipping');
                continue;
            }

            // Pick the tenant's snssale.com (or configured suffix) domain.
            // Prefer the one matching the gateway suffix; fall back to the
            // first domain row only if nothing matches.
            $suffix = config('auth-gateway.tenant_subdomain_suffix', 'snssale.com');
            $allDomains = $tenant->domains()->pluck('domain')->all();

            $subdomain = null;
            foreach ($allDomains as $d) {
                if (preg_match('/^([a-z0-9-]+)\.'.preg_quote($suffix, '/').'$/i', $d, $m)) {
                    $subdomain = $m[1];
                    break;
                }
            }

            if (! $subdomain && ! empty($allDomains)) {
                // No suffix match — strip host parts from first domain
                $subdomain = explode('.', $allDomains[0])[0];
            }
            if (! $subdomain) {
                $subdomain = strtolower(preg_replace('/[^a-z0-9-]+/i', '-', $tenant->name)) ?: 'store';
            }

            foreach ($posUsers as $pu) {
                $email = strtolower(trim((string) $pu->email));
                $existing = DB::table('gateway_users')->where('email', $email)->first();

                if ($existing) {
                    $this->line("    [skip] {$email} already in gateway_users (id={$existing->id})");
                    $userId = $existing->id;
                    $totals['users_skipped']++;
                } else {
                    $this->line("    [insert] {$email}");
                    if (! $dryRun) {
                        $userId = DB::table('gateway_users')->insertGetId([
                            'email'             => $email,
                            // The pos_users password hash is bcrypt-compatible. Reuse it
                            // so the merchant's existing password keeps working.
                            'password_hash'     => $tenant->run(fn () =>
                                DB::table('pos_users')->where('id', $pu->id)->value('password')
                            ),
                            'name'              => trim(($pu->firstname ?? '').' '.($pu->lastname ?? '')) ?: null,
                            'email_verified_at' => now(),
                            'status'            => $pu->status ? 'active' : 'suspended',
                            'created_at'        => now(),
                            'updated_at'        => now(),
                        ]);
                    } else {
                        $userId = 0;
                    }
                    $totals['users_inserted']++;
                }

                if ($userId) {
                    $existingBridge = DB::table('gateway_user_tenants')
                        ->where('user_id', $userId)
                        ->where('tenant_id', $tenant->id)
                        ->exists();
                    if (! $existingBridge && ! $dryRun) {
                        DB::table('gateway_user_tenants')->insert([
                            'user_id'          => $userId,
                            'tenant_id'        => $tenant->id,
                            'tenant_subdomain' => $subdomain,
                            'role'             => $pu->role ?? 'cashier',
                            'is_owner'         => ($pu->role ?? null) === 'owner' ? 1 : 0,
                            'created_at'       => now(),
                            'updated_at'       => now(),
                        ]);
                        $totals['bridges_inserted']++;
                    }
                }
            }

            // gateway_tenants row for the whole tenant
            $existingTenant = DB::table('gateway_tenants')->where('tenant_id', $tenant->id)->exists();
            if (! $existingTenant && ! $dryRun) {
                $ownerEmail = $tenant->owner_email ?? $posUsers->firstWhere('role', 'owner')?->email ?? $posUsers->first()->email;
                $ownerId = DB::table('gateway_users')->where('email', strtolower($ownerEmail))->value('id');
                DB::table('gateway_tenants')->insert([
                    'tenant_id'     => $tenant->id,
                    'subdomain'     => $subdomain,
                    'store_name'    => $tenant->name,
                    'owner_user_id' => $ownerId,
                    'status'        => 'active',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
                $totals['tenant_rows_inserted']++;
                $this->line("    [insert tenant row] subdomain={$subdomain}");
            } elseif ($existingTenant) {
                $this->line("    [skip tenant row] already exists");
            }
        }

        $this->newLine();
        $this->info('Done.');
        $this->table(['metric', 'count'], collect($totals)->map(fn ($v, $k) => [$k, $v])->values()->all());

        if ($dryRun) {
            $this->comment('Dry run — no writes were performed.');
        }

        return self::SUCCESS;
    }

    protected function resolveTenant(string $key): ?Tenant
    {
        // UUID match first
        $byId = Tenant::find($key);
        if ($byId) {
            return $byId;
        }

        // Domain match
        return Tenant::whereHas('domains', fn ($q) => $q->where('domain', $key))->first();
    }
}
