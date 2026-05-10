<?php

namespace Fujiba\AuthGateway\Services;

use App\Models\Tenant;
use App\Services\SuperAdmin\TenantCloner;
use Fujiba\AuthGateway\Exceptions\ProvisioningException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Provision a brand-new tenant for a fresh signup.
 *
 * Steps (each is its own try/catch with named step in ProvisioningException):
 *
 *   1. central_create     — Tenant::create() + domain attach
 *   2. seed_template      — pipe tenant_master_template via TenantCloner
 *   3. tenants_migrate    — Artisan tenants:migrate for the new tenant
 *   4. tenant_seed        — within tenant, set channel name + JPY/ja, add tax categories,
 *                            create pos_outlet + pos_user
 *
 * Returns: ['tenant' => Tenant, 'pos_user_id' => int]
 *
 * Failure modes leave traces:
 *   - central row created but seed failed → we mark gateway_tenants.status='failed'
 *     so cleanup is one query. Caller is expected to surface a friendly error.
 */
class TenantProvisioner
{
    public function __construct(
        protected TenantCloner $cloner
    ) {}

    public function provision(array $payload): array
    {
        // Required: subdomain, store_name, owner_email, owner_password (plain).
        $subdomain   = $payload['subdomain'];
        $storeName   = $payload['store_name'];
        $email       = $payload['owner_email'];
        $passwordPlain = $payload['owner_password'];
        $name        = $payload['owner_name'] ?? null;

        $tenantId = (string) Str::uuid();
        $domainSuffix = config('auth-gateway.tenant_subdomain_suffix', 'snssale.com');
        $domain = "{$subdomain}.{$domainSuffix}";

        $tenant = null;

        try {
            $tenant = Tenant::create([
                'id'          => $tenantId,
                'name'        => $storeName,
                'email'       => $email,
                'plan'        => 'self_signup',
                'plan_id'     => null,
                'status'      => true,
                'owner_email' => $email,
            ]);

            $tenant->domains()->firstOrCreate(['domain' => $domain]);
        } catch (\Throwable $e) {
            throw new ProvisioningException(
                "central_create failed: {$e->getMessage()}",
                'central_create',
                $e
            );
        }

        try {
            $this->cloner->seedFromMasterTemplate($tenant);
        } catch (\Throwable $e) {
            throw new ProvisioningException(
                "seed_template failed: {$e->getMessage()}",
                'seed_template',
                $e
            );
        }

        try {
            Artisan::call('tenants:migrate', [
                '--tenants' => [$tenant->id],
                '--force'   => true,
            ]);
        } catch (\Throwable $e) {
            throw new ProvisioningException(
                "tenants_migrate failed: {$e->getMessage()}",
                'tenants_migrate',
                $e
            );
        }

        $posUserId = null;

        try {
            $posUserId = $this->seedTenantContent(
                $tenant,
                $storeName,
                $email,
                $passwordPlain,
                $name
            );
        } catch (\Throwable $e) {
            throw new ProvisioningException(
                "tenant_seed failed: {$e->getMessage()}",
                'tenant_seed',
                $e
            );
        }

        return [
            'tenant'       => $tenant,
            'pos_user_id'  => $posUserId,
            'subdomain'    => $subdomain,
            'domain'       => $domain,
            'tenant_id'    => $tenantId,
        ];
    }

    /**
     * Bootstrap into the new tenant DB and:
     *   - rename the default channel to the merchant's store name
     *   - switch to JPY currency + ja locale (insert if needed)
     *   - add JP tax categories (8% reduced, 10% standard)
     *   - create one pos_outlet
     *   - create one pos_user (role=owner) with the signup credentials
     *
     * Returns the new pos_user.id.
     */
    protected function seedTenantContent(
        Tenant $tenant,
        string $storeName,
        string $email,
        string $passwordPlain,
        ?string $name
    ): int {
        $tenant->run(function () use ($storeName, $email, $passwordPlain, $name) {
            // 1. Currency JPY + Locale ja
            $jpyId = $this->ensureCurrency('JPY', '¥');
            $jaId  = $this->ensureLocale('ja', 'Japanese');

            // 2. Channel update — master template ships with code='default',
            //    USD currency, en locale.
            DB::table('channels')->where('code', 'default')->update([
                'base_currency_id'  => $jpyId,
                'default_locale_id' => $jaId,
                'hostname'          => null, // tenancy resolves via domains table
                'updated_at'        => now(),
            ]);

            $channelId = DB::table('channels')->where('code', 'default')->value('id');

            // Channel translation (the merchant-facing name)
            DB::table('channel_translations')
                ->updateOrInsert(
                    ['channel_id' => $channelId, 'locale' => 'ja'],
                    ['name' => $storeName]
                );
            DB::table('channel_translations')
                ->updateOrInsert(
                    ['channel_id' => $channelId, 'locale' => 'en'],
                    ['name' => $storeName]
                );

            // Channel <-> currency / locale pivots
            DB::table('channel_currencies')->updateOrInsert(
                ['channel_id' => $channelId, 'currency_id' => $jpyId],
                []
            );
            DB::table('channel_locales')->updateOrInsert(
                ['channel_id' => $channelId, 'locale_id' => $jaId],
                []
            );

            // 3. Tax categories (idempotent)
            foreach (config('auth-gateway.seed.tax_rates', []) as $tax) {
                $taxCatId = DB::table('tax_categories')
                    ->updateOrInsert(
                        ['code' => $tax['code']],
                        [
                            'name'        => $tax['name'],
                            'description' => $tax['name'],
                            'updated_at'  => now(),
                            'created_at'  => now(),
                        ]
                    );

                $taxCatId = DB::table('tax_categories')->where('code', $tax['code'])->value('id');

                $rateId = DB::table('tax_rates')->updateOrInsert(
                    ['identifier' => $tax['code'].'_jp'],
                    [
                        'is_zip'      => 0,
                        'country'     => 'JP',
                        'tax_rate'    => $tax['rate'],
                        'updated_at'  => now(),
                        'created_at'  => now(),
                    ]
                );

                $rateId = DB::table('tax_rates')->where('identifier', $tax['code'].'_jp')->value('id');

                DB::table('tax_categories_tax_rates')->updateOrInsert(
                    ['tax_category_id' => $taxCatId, 'tax_rate_id' => $rateId],
                    []
                );
            }

            // 4. Inventory source — master template already has one ('default')
            $inventorySourceId = DB::table('inventory_sources')->where('code', 'default')->value('id');
            if (! $inventorySourceId) {
                $inventorySourceId = DB::table('inventory_sources')->insertGetId([
                    'code'         => 'default',
                    'name'         => 'Default Warehouse',
                    'description'  => 'Default warehouse — auto-created at signup',
                    'contact_name' => $name ?: $storeName,
                    'contact_email' => $email,
                    'street'       => '—',
                    'country'      => 'JP',
                    'priority'     => 0,
                    'status'       => 1,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }

            DB::table('channel_inventory_sources')->updateOrInsert(
                ['channel_id' => $channelId, 'inventory_source_id' => $inventorySourceId],
                []
            );

            // 5. Pos outlet
            $outletId = DB::table('pos_outlets')->insertGetId([
                'name'                => $storeName,
                'email'               => $email,
                'inventory_source_id' => $inventorySourceId,
                'country'             => 'JP',
                'status'              => 1,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            // 6. Pos user (the signup credentials)
            $posUserId = DB::table('pos_users')->insertGetId([
                'outlet_id'  => $outletId,
                'email'      => $email,
                'password'   => Hash::make($passwordPlain),
                'firstname'  => $name ?: 'Owner',
                'lastname'   => '',
                'image'      => '',
                'role'       => 'owner',
                'status'     => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Stash the id where the outer closure can grab it.
            DB::statement("SET @gateway_provisioner_pos_user_id = {$posUserId}");
        });

        // Pull the id back out (set in the same MySQL session inside ->run())
        $posUserId = $tenant->run(fn () => (int) DB::selectOne('SELECT @gateway_provisioner_pos_user_id AS id')->id);

        if (! $posUserId) {
            // Last-resort fallback: query by email
            $posUserId = (int) ($tenant->run(fn () =>
                DB::table('pos_users')->where('email', $email)->value('id')
            ) ?? 0);
        }

        if (! $posUserId) {
            throw new \RuntimeException('pos_user creation succeeded but id was not recoverable.');
        }

        return $posUserId;
    }

    protected function ensureCurrency(string $code, string $symbol): int
    {
        $existing = DB::table('currencies')->where('code', $code)->value('id');
        if ($existing) {
            return (int) $existing;
        }

        return DB::table('currencies')->insertGetId([
            'code'       => $code,
            'symbol'     => $symbol,
            'name'       => $code,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function ensureLocale(string $code, string $name): int
    {
        $existing = DB::table('locales')->where('code', $code)->value('id');
        if ($existing) {
            return (int) $existing;
        }

        return DB::table('locales')->insertGetId([
            'code'       => $code,
            'name'       => $name,
            'direction'  => 'ltr',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Hard-cleanup helper for failed provisions. Drops the tenant DB row +
     * its database. Use only when status='failed' and the row is orphaned.
     */
    public function destroyFailed(Tenant $tenant): void
    {
        try {
            $tenant->database()->makeCredentials();
            $tenant->database()->getTenantDatabaseManager()->deleteDatabase($tenant);
        } catch (\Throwable $e) {
            Log::warning("[AuthGateway] failed to drop tenant DB: {$e->getMessage()}");
        }
        $tenant->domains()->delete();
        $tenant->delete();
    }
}
