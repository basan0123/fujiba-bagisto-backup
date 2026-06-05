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

        $seed = null;

        try {
            $seed = $this->seedTenantContent(
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
            'tenant'              => $tenant,
            'pos_user_id'         => $seed['pos_user_id'],
            'channel_id'          => $seed['channel_id'],
            'inventory_source_id' => $seed['inventory_source_id'],
            'outlet_id'           => $seed['outlet_id'],
            'subdomain'           => $subdomain,
            'domain'              => $domain,
            'tenant_id'           => $tenantId,
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
     * Returns ['pos_user_id', 'channel_id', 'inventory_source_id', 'outlet_id'].
     * The channel/inventory ids must travel back to the caller so the gateway
     * can bake them into the JWT + signup response, exactly as login() does —
     * a token without channel_id makes ValidatePosJwtTenant fall back to
     * host/subdomain channel resolution. See [pos-jwt-channel-resolution].
     */
    protected function seedTenantContent(
        Tenant $tenant,
        string $storeName,
        string $email,
        string $passwordPlain,
        ?string $name
    ): array {
        $tenant->run(function () use ($storeName, $email, $passwordPlain, $name) {
            // 1. Currency JPY + Locale ja
            $jpyId = $this->ensureCurrency('JPY', '¥');
            $jaId  = $this->ensureLocale('ja', 'Japanese');

            // 2. Channel update — master template ships with code='default',
            //    USD currency, en locale.
            //
            // BUG #2 GUARD: the whole flow hard-depends on the template carrying
            // a code='default' channel. If a future template rebuild/restore
            // drops it (this has happened — see the locale-migration dump that
            // reverted central rows), update() silently affects 0 rows, ->value()
            // returns null, and every downstream insert (translations, pivots,
            // outlet, the JWT channel_id claim) goes null → the new shop comes up
            // channel-null and getCurrentChannel()/LocaleMiddleware 500s. So:
            // CREATE the default channel if it's absent rather than failing.
            $existingChannelId = DB::table('channels')->where('code', 'default')->value('id');

            if (! $existingChannelId) {
                $channelId = DB::table('channels')->insertGetId([
                    'code'              => 'default',
                    'theme'             => null,
                    'base_currency_id'  => $jpyId,
                    'default_locale_id' => $jaId,
                    'root_category_id'  => DB::table('categories')->min('id') ?? 1,
                    'hostname'          => null,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            } else {
                DB::table('channels')->where('code', 'default')->update([
                    'base_currency_id'  => $jpyId,
                    'default_locale_id' => $jaId,
                    'hostname'          => null, // tenancy resolves via domains table
                    'updated_at'        => now(),
                ]);
                $channelId = $existingChannelId;
            }

            if (! $channelId) {
                // Belt-and-suspenders: never let a null channel id propagate.
                throw new \RuntimeException(
                    'tenant_seed: default channel could not be resolved or created'
                );
            }

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
            //
            // BUG #1 FIX: pin the outlet to the channel explicitly. The
            // channel_id column exists on new tenants (the Webkul/Pos migration
            // runs via tenants:migrate before this seed) and $channelId is in
            // scope. Without it, POS login falls back to the
            // channel_inventory_sources pivot — works for a single-channel shop
            // by luck, but it is the exact ambiguity behind the "wrong outlet"
            // class of bug the REST-JWT redesign was meant to kill.
            $outletId = DB::table('pos_outlets')->insertGetId([
                'name'                => $storeName,
                'email'               => $email,
                'channel_id'          => $channelId,
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

            // 7. Admin-panel user — so the owner can sign in to /admin with the
            //    SAME credentials as the POS. The cloned template already ships
            //    the `admins` table + an Administrator role (perm='all'); we
            //    reuse that role (fall back to the first role / id 1) and upsert
            //    by email so re-provisioning never duplicates. The template's
            //    default admin@example.com row is left in place. Admin login is
            //    a standard Laravel guard('admin')->attempt() (bcrypt) gated on
            //    status, so a Hash::make password with status=1 works directly.
            $adminRoleId = (int) (
                DB::table('roles')->where('permission_type', 'all')->value('id')
                ?? DB::table('roles')->min('id')
                ?? 1
            );

            DB::table('admins')->updateOrInsert(
                ['email' => $email],
                [
                    'name'       => $name ?: 'Owner',
                    'password'   => Hash::make($passwordPlain),
                    'role_id'    => $adminRoleId,
                    'status'     => 1,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            // Stash the ids where the outer closure can grab them. Each ->run()
            // is its own connection bootstrap but stays in the same MySQL
            // session, so user-defined session vars survive to the read below.
            DB::statement("SET @gw_pos_user_id = {$posUserId}");
            DB::statement('SET @gw_channel_id = ' . ((int) $channelId));
            DB::statement('SET @gw_inventory_source_id = ' . ((int) $inventorySourceId));
            DB::statement("SET @gw_outlet_id = {$outletId}");
        });

        // Pull the ids back out (set in the same MySQL session inside ->run()).
        $ids = $tenant->run(fn () => DB::selectOne(
            'SELECT @gw_pos_user_id AS pos_user_id, @gw_channel_id AS channel_id, '
            . '@gw_inventory_source_id AS inventory_source_id, @gw_outlet_id AS outlet_id'
        ));

        $posUserId         = (int) ($ids->pos_user_id ?? 0);
        $channelId         = (int) ($ids->channel_id ?? 0);
        $inventorySourceId = (int) ($ids->inventory_source_id ?? 0);
        $outletId          = (int) ($ids->outlet_id ?? 0);

        if (! $posUserId) {
            // Last-resort fallback: query by email (and re-derive the rest).
            $fallback = $tenant->run(function () use ($email) {
                $u = DB::table('pos_users')->where('email', $email)->first();
                if (! $u) {
                    return null;
                }
                $outlet = $u->outlet_id ? DB::table('pos_outlets')->where('id', $u->outlet_id)->first() : null;
                $invId  = $outlet->inventory_source_id ?? (int) (DB::table('inventory_sources')->value('id') ?? 0);
                $chId   = (int) (DB::table('channel_inventory_sources')
                    ->where('inventory_source_id', $invId)
                    ->value('channel_id') ?? (DB::table('channels')->value('id') ?? 0));

                return [
                    'pos_user_id'         => (int) $u->id,
                    'channel_id'          => $chId,
                    'inventory_source_id' => (int) $invId,
                    'outlet_id'           => (int) ($u->outlet_id ?? 0),
                ];
            });

            if ($fallback) {
                $posUserId         = $fallback['pos_user_id'];
                $channelId         = $fallback['channel_id'];
                $inventorySourceId = $fallback['inventory_source_id'];
                $outletId          = $fallback['outlet_id'];
            }
        }

        if (! $posUserId) {
            throw new \RuntimeException('pos_user creation succeeded but id was not recoverable.');
        }

        // BUG #3 FIX — write the central user_channel_map row at signup.
        //
        // The per-tenant REST /api/v1/pos/login fast-path resolves the tenant by
        // looking up the email in the central user_channel_map. seedTenantContent
        // creates the pos_user via raw DB::table()->insertGetId(), which bypasses
        // PosUserObserver, so the map row was never written — the owner's first
        // login fell back to scanning every tenant DB (slow, O(tenants)). This
        // mirrors BackfillPosUserChannelMap::processTenant() so a new signup
        // never needs the backfill. Idempotent (updateOrInsert).
        try {
            $this->writeUserChannelMap(
                $tenant,
                $email,
                $name,
                $passwordPlain,
                $channelId,
                $inventorySourceId
            );
        } catch (\Throwable $e) {
            // Non-fatal: login still works via the tenant-scan fallback, and the
            // backfill command can repair it later. Don't fail the whole signup
            // over the fast-path cache row.
            Log::warning('[AuthGateway] user_channel_map write failed at signup', [
                'email'   => $email,
                'tenant'  => (string) $tenant->id,
                'message' => $e->getMessage(),
            ]);
        }

        return [
            'pos_user_id'         => $posUserId,
            'channel_id'          => $channelId,
            'inventory_source_id' => $inventorySourceId,
            'outlet_id'           => $outletId,
        ];
    }

    /**
     * Upsert the central user_channel_map row for a freshly-provisioned owner.
     *
     * Lives on the central (`mysql`) connection. Mirrors
     * App\Console\Commands\BackfillPosUserChannelMap::processTenant():
     *   - firstOrCreate the central App\Models\User by email (reusing the
     *     pos_user password hash so credentials line up), then
     *   - updateOrInsert the user_channel_map keyed on (user_id, channel_id).
     */
    protected function writeUserChannelMap(
        Tenant $tenant,
        string $email,
        ?string $name,
        string $passwordPlain,
        int $channelId,
        int $inventorySourceId
    ): void {
        // Reuse the pos_user's stored hash so the central user mirrors it; if it
        // can't be read for any reason, hash the plain password we already hold.
        $posUserHash = $tenant->run(
            fn () => DB::table('pos_users')->where('email', $email)->value('password')
        );

        $centralUser = \App\Models\User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => $name ?: $email,
                'password' => $posUserHash ?: Hash::make($passwordPlain),
            ]
        );

        \App\Models\UserChannelMap::on('mysql')->updateOrInsert(
            [
                'user_id'    => $centralUser->id,
                'channel_id' => $channelId,
            ],
            [
                'tenant_id'           => (string) $tenant->id,
                'inventory_source_id' => $inventorySourceId ?: null,
                'is_active'           => true,
                'updated_at'          => now(),
                'created_at'          => DB::raw('IFNULL(created_at, NOW())'),
            ]
        );
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
