<?php

namespace Fujiba\AuthGateway\Http\Controllers;

use Fujiba\AuthGateway\Exceptions\InvalidJwtException;
use Fujiba\AuthGateway\Exceptions\ProvisioningException;
use Fujiba\AuthGateway\Http\Requests\LoginRequest;
use Fujiba\AuthGateway\Http\Requests\SignupRequest;
use Fujiba\AuthGateway\Services\JwtService;
use Fujiba\AuthGateway\Services\RateLimiter as GatewayRateLimiter;
use Fujiba\AuthGateway\Services\Slugger;
use Fujiba\AuthGateway\Services\TenantProvisioner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(
        protected JwtService           $jwt,
        protected Slugger              $slugger,
        protected TenantProvisioner    $provisioner,
        protected GatewayRateLimiter   $limiter,
    ) {}

    public function signup(SignupRequest $req): JsonResponse
    {
        $ip = (string) $req->ip();
        $ua = (string) $req->userAgent();
        $rl = config('auth-gateway.rate_limits');

        if ($msg = $this->limiter->signupOverLimit($ip, $rl['signup_per_ip_per_hour'], $rl['signup_per_ip_per_day'])) {
            $this->limiter->recordSignup($req->input('email'), $ip, $ua, false, 'rate_limited');
            return response()->json(['error' => 'rate_limited', 'message' => $msg], 429);
        }

        $email = strtolower(trim((string) $req->input('email')));
        $existing = DB::table('gateway_users')->where('email', $email)->exists();
        if ($existing) {
            $this->limiter->recordSignup($email, $ip, $ua, false, 'email_exists');
            return response()->json([
                'error'   => 'email_exists',
                'message' => 'An account with this email already exists. Try signing in.',
            ], 409);
        }

        $storeName = trim((string) $req->input('store_name'));

        // Allocate a free subdomain inside the central transaction below.
        // We do it here BEFORE any tenant DB work so collisions surface
        // before we touch heavy filesystem stuff.
        try {
            $subdomain = $this->slugger->nextAvailable($storeName, function (string $candidate): bool {
                return ! DB::table('gateway_tenants')->where('subdomain', $candidate)->exists();
            });
        } catch (\Throwable $e) {
            $this->limiter->recordSignup($email, $ip, $ua, false, 'slug_alloc');
            return response()->json([
                'error'   => 'slug_alloc',
                'message' => 'Could not allocate a free subdomain. Try a different store name.',
            ], 422);
        }

        // Provision the new tenant. This is async-safe: the central row +
        // tenant DB + seed are visible immediately on success, and on failure
        // we mark gateway_tenants.status='failed' so support can see what's
        // up.
        $now = now();
        $passwordHash = Hash::make($req->input('password'));

        // Step A — central insert for the gateway side. Done in a tx so a
        // duplicate email race is rejected without partial state.
        try {
            $userId = DB::transaction(function () use ($email, $passwordHash, $req, $now) {
                return DB::table('gateway_users')->insertGetId([
                    'email'             => $email,
                    'password_hash'     => $passwordHash,
                    'name'              => $req->input('name'),
                    'email_verified_at' => $now, // Phase 1: auto-verify
                    'status'            => 'active',
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
            });
        } catch (\Throwable $e) {
            // most likely a UNIQUE collision from a race
            $this->limiter->recordSignup($email, $ip, $ua, false, 'central_insert');
            return response()->json([
                'error'   => 'central_insert',
                'message' => 'Account creation failed. Try again.',
            ], 500);
        }

        // Step B — tenant provisioning (DB clone + migrate + seed). Heavy.
        try {
            $result = $this->provisioner->provision([
                'subdomain'      => $subdomain,
                'store_name'     => $storeName,
                'owner_email'    => $email,
                'owner_password' => $req->input('password'),
                'owner_name'     => $req->input('name'),
            ]);
        } catch (ProvisioningException $e) {
            Log::error('[AuthGateway] provision failed', [
                'email' => $email, 'subdomain' => $subdomain,
                'step' => $e->step, 'message' => $e->getMessage(),
            ]);

            // Roll back gateway_users so the merchant can retry without
            // hitting "email exists". Tenant row may or may not exist
            // depending on which step failed; we leave it for ops cleanup
            // tagged with status='failed' if it got that far.
            DB::table('gateway_users')->where('id', $userId)->delete();

            $this->limiter->recordSignup($email, $ip, $ua, false, 'provision:'.$e->step);
            return response()->json([
                'error'   => 'provisioning_failed',
                'message' => 'Tenant setup failed. Please try again or contact support.',
                'step'    => $e->step,
            ], 500);
        }

        // Step C — final central inserts (gateway_user_tenants + gateway_tenants)
        try {
            DB::transaction(function () use ($userId, $result, $storeName, $now) {
                DB::table('gateway_user_tenants')->insert([
                    'user_id'          => $userId,
                    'tenant_id'        => $result['tenant_id'],
                    'tenant_subdomain' => $result['subdomain'],
                    'role'             => 'owner',
                    'is_owner'         => 1,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);
                DB::table('gateway_tenants')->insert([
                    'tenant_id'     => $result['tenant_id'],
                    'subdomain'     => $result['subdomain'],
                    'store_name'    => $storeName,
                    'owner_user_id' => $userId,
                    'status'        => 'active',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('[AuthGateway] post-provision insert failed', [
                'email' => $email, 'subdomain' => $subdomain, 'message' => $e->getMessage(),
            ]);
            // Tenant DB exists, central rows may be partial — caller can retry login.
        }

        $this->limiter->recordSignup($email, $ip, $ua, true);

        // Bake the freshly-provisioned outlet's channel + inventory source into
        // the JWT and response, exactly as login() does. Without channel_id the
        // tenant-side ValidatePosJwtTenant falls back to host/subdomain channel
        // resolution (defaults to channel 1) — fine for a single-channel fresh
        // tenant by luck, but the client (pinSessionFromGateway) would store
        // channelId=null and the catalog/image paths would be unscoped until
        // fetchCurrentAgent ran. Carry them so signup matches login. See
        // [pos-jwt-channel-resolution].
        $channelId         = (int) ($result['channel_id'] ?? 0) ?: null;
        $inventorySourceId = (int) ($result['inventory_source_id'] ?? 0) ?: null;

        $token = $this->issueToken(
            posUserId: (int) $result['pos_user_id'],
            gatewayUserId: $userId,
            email: $email,
            name: $req->input('name'),
            tenantId: $result['tenant_id'],
            subdomain: $result['subdomain'],
            role: 'owner',
            channelId: $channelId,
            inventorySourceId: $inventorySourceId,
        );

        return response()->json([
            'jwt'                 => $token['jwt'],
            'expires_at'          => $token['exp'],
            'tenant_subdomain'    => $result['subdomain'],
            'tenant_id'           => $result['tenant_id'],
            'store_name'          => $storeName,
            'role'                => 'owner',
            'channel_id'          => $channelId,
            'inventory_source_id' => $inventorySourceId,
            'tenant_url'          => "https://{$result['subdomain']}." . config('auth-gateway.tenant_subdomain_suffix'),
        ], 201);
    }

    public function login(LoginRequest $req): JsonResponse
    {
        $email = strtolower(trim((string) $req->input('email')));
        $ip = (string) $req->ip();
        $ua = (string) $req->userAgent();
        $rl = config('auth-gateway.rate_limits');

        // Generic lockout BEFORE we even look the user up — defends enumeration.
        if ($msg = $this->limiter->loginLockoutMessage($email, $rl['login_fail_per_email_15m'], $rl['login_lockout_per_email'])) {
            return response()->json(['error' => 'rate_limited', 'message' => $msg], 429);
        }

        $user = DB::table('gateway_users')->where('email', $email)->first();
        if (! $user || ! Hash::check((string) $req->input('password'), $user->password_hash)) {
            $this->limiter->recordLoginFailure($email, $ip, $ua);
            return response()->json([
                'error'   => 'invalid_credentials',
                'message' => 'Email or password is incorrect.',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'error'   => 'account_'.$user->status,
                'message' => "This account is {$user->status}. Contact support.",
            ], 403);
        }

        $bridge = DB::table('gateway_user_tenants')
            ->where('user_id', $user->id)
            ->orderBy('id') // Phase 1: pick first; multi-store comes later
            ->first();

        if (! $bridge) {
            return response()->json([
                'error'   => 'no_tenant',
                'message' => 'No tenant is associated with this account. Contact support.',
            ], 422);
        }

        $tenantMeta = DB::table('gateway_tenants')->where('tenant_id', $bridge->tenant_id)->first();
        if (! $tenantMeta || $tenantMeta->status !== 'active') {
            return response()->json([
                'error'   => 'tenant_inactive',
                'message' => 'Your store is not active. Contact support.',
            ], 422);
        }

        DB::table('gateway_users')
            ->where('id', $user->id)
            ->update(['last_login_at' => now(), 'updated_at' => now()]);

        // Resolve the pos_users.id within the target tenant — auth:pos guard
        // resolves the user via `sub` claim which must point at pos_users.id.
        $tenantModel = \App\Models\Tenant::find($bridge->tenant_id);
        if (! $tenantModel) {
            return response()->json([
                'error' => 'tenant_missing',
                'message' => 'Your tenant database is unreachable. Contact support.',
            ], 422);
        }
        // Resolve pos_user.id AND the channel/inventory_source for that user's
        // outlet inside the tenant DB. The JWT MUST carry channel_id — the
        // tenant-side middleware (ValidatePosJwtTenant / SetTenantStorageDisk)
        // and image-path resolution read it from the token. Without it a
        // multi-store login (e.g. the mmfood outlet) defaulted to channel 1
        // and showed kimono products + tenants/1/ images after a store-switch.
        $resolved = $tenantModel->run(function () use ($user) {
            $posUserId = (int) (DB::table('pos_users')->where('email', $user->email)->value('id') ?? 0);
            if (! $posUserId) {
                return null;
            }

            $outletId = (int) (DB::table('pos_users')->where('id', $posUserId)->value('outlet_id') ?? 0);
            $outlet   = $outletId ? DB::table('pos_outlets')->where('id', $outletId)->first() : null;

            $inventorySourceId = $outlet->inventory_source_id ?? null;

            // Prefer the outlet's own channel_id column (added 2026-05-18);
            // older clones lack it, so guard + fall back to the source→channel
            // map. See [pos-jwt-channel-resolution].
            $channelId = (property_exists($outlet ?? new \stdClass, 'channel_id') && ! empty($outlet->channel_id))
                ? (int) $outlet->channel_id
                : null;

            if (! $channelId && $inventorySourceId) {
                $channelId = (int) (DB::table('channel_inventory_sources')
                    ->where('inventory_source_id', $inventorySourceId)
                    ->value('channel_id') ?? 0);
            }

            if (! $channelId) {
                $channelId = (int) (DB::table('channels')->value('id') ?? 1);
            }
            if (! $inventorySourceId) {
                $inventorySourceId = (int) (DB::table('inventory_sources')->value('id') ?? 1);
            }

            return [
                'pos_user_id'         => $posUserId,
                'channel_id'          => $channelId,
                'inventory_source_id' => (int) $inventorySourceId,
            ];
        });

        if (! $resolved) {
            return response()->json([
                'error'   => 'no_pos_user',
                'message' => 'No POS user record found in your store. Contact support.',
            ], 422);
        }

        $posUserId = $resolved['pos_user_id'];

        $token = $this->issueToken(
            posUserId: $posUserId,
            gatewayUserId: (int) $user->id,
            email: $user->email,
            name: $user->name,
            tenantId: $bridge->tenant_id,
            subdomain: $bridge->tenant_subdomain,
            role: $bridge->role,
            channelId: $resolved['channel_id'],
            inventorySourceId: $resolved['inventory_source_id'],
        );

        return response()->json([
            'jwt'              => $token['jwt'],
            'expires_at'       => $token['exp'],
            'tenant_subdomain' => $bridge->tenant_subdomain,
            'tenant_id'        => $bridge->tenant_id,
            'store_name'       => $tenantMeta->store_name,
            'role'             => $bridge->role,
            'channel_id'          => $resolved['channel_id'],
            'inventory_source_id' => $resolved['inventory_source_id'],
            'tenant_url'       => "https://{$bridge->tenant_subdomain}." . config('auth-gateway.tenant_subdomain_suffix'),
        ]);
    }

    public function logout(Request $req): JsonResponse
    {
        $bearer = $this->extractBearer($req);
        if ($bearer === null) {
            return response()->json(['error' => 'no_token'], 400);
        }

        try {
            $claims = $this->jwt->verify($bearer);
        } catch (InvalidJwtException $e) {
            // Treat as already-revoked — idempotent
            return response()->json(['ok' => true]);
        }

        $jti = $claims['jti'] ?? null;
        $exp = (int) ($claims['exp'] ?? 0);
        if ($jti && $exp > time()) {
            $this->jwt->revoke($jti, $exp);
        }

        return response()->json(['ok' => true]);
    }

    public function me(Request $req): JsonResponse
    {
        $bearer = $this->extractBearer($req);
        if ($bearer === null) {
            return response()->json(['error' => 'no_token'], 401);
        }

        try {
            $claims = $this->jwt->verify($bearer);
        } catch (InvalidJwtException $e) {
            return response()->json(['error' => 'invalid_token', 'message' => $e->getMessage()], 401);
        }

        // The gateway user is identified by the `gateway_user_id` claim, NOT
        // `sub`. `sub` is the tenant-side pos_users.id (the auth:pos guard's key
        // — see issueToken()), so looking up gateway_users by `sub` returned
        // user_not_found for every gateway-issued token. Fall back to `sub`
        // only for any legacy token that predates the gateway_user_id claim.
        $userId = $claims['gateway_user_id'] ?? $claims['sub'] ?? null;
        if (! $userId) {
            return response()->json(['error' => 'invalid_token'], 401);
        }

        $user = DB::table('gateway_users')->where('id', $userId)->first();
        if (! $user) {
            return response()->json(['error' => 'user_not_found'], 401);
        }

        $bridge = DB::table('gateway_user_tenants')
            ->where('user_id', $userId)
            ->orderBy('id')
            ->first();

        $tenantMeta = $bridge
            ? DB::table('gateway_tenants')->where('tenant_id', $bridge->tenant_id)->first()
            : null;

        return response()->json([
            'user' => [
                'id'    => (int) $user->id,
                'email' => $user->email,
                'name'  => $user->name,
            ],
            'tenant' => $tenantMeta ? [
                'id'         => $tenantMeta->tenant_id,
                'subdomain'  => $tenantMeta->subdomain,
                'store_name' => $tenantMeta->store_name,
                'status'     => $tenantMeta->status,
                'role'       => $bridge->role,
            ] : null,
        ]);
    }

    public function forgotPassword(Request $req): JsonResponse
    {
        $req->validate([
            'email' => ['required', 'email:filter', 'max:190'],
        ]);

        $email = strtolower(trim((string) $req->input('email')));
        $ip = (string) $req->ip();
        $rl = config('auth-gateway.rate_limits');

        // Phase 1: stub — log + always return 200 (don't leak account existence).
        if (! $this->limiter->forgotPasswordOverLimit($email, $rl['forgot_per_email_per_hour'])) {
            $this->limiter->recordForgotPassword($email, $ip);
            Log::info('[AuthGateway] forgot_password (stub)', ['email' => $email, 'ip' => $ip]);
            // Phase 4 will dispatch the real reset email here.
        }

        return response()->json([
            'ok'      => true,
            'message' => 'If an account exists for that email, a reset link has been sent.',
        ]);
    }

    /* ---------------- helpers ---------------- */

    /**
     * Issue a token compatible with the existing tenant-side `auth:pos` guard
     * (PHPOpenSourceSaver\JWTAuth) AND the gateway's own /me endpoint.
     *
     *   sub             = pos_users.id (the auth guard's expected key)
     *   gateway_user_id = gateway_users.id (used by /me to resolve account)
     *   tenant_id       = stancl tenant uuid (used by ValidatePosJwtTenant)
     */
    protected function issueToken(
        int $posUserId,
        int $gatewayUserId,
        string $email,
        ?string $name,
        string $tenantId,
        string $subdomain,
        string $role,
        ?int $channelId = null,
        ?int $inventorySourceId = null
    ): array {
        $claims = [
            'sub'              => (string) $posUserId,
            'gateway_user_id'  => $gatewayUserId,
            'email'            => $email,
            'name'             => $name,
            'tenant_id'        => $tenantId,
            'tenant_subdomain' => $subdomain,
            'role'             => $role,
            // PRV is JWT-Auth's "Provider" claim — it pins the token to a
            // specific User class so a token forged against another model
            // can't be reused. Keep it consistent with the existing pos
            // login output (sha1(Webkul\Pos\Models\User::class)).
            'prv'              => sha1(\Webkul\Pos\Models\User::class),
        ];

        // channel_id / inventory_source_id let the tenant middleware scope
        // products + image storage paths to the right outlet's channel. The
        // legacy per-tenant /api/v1/pos/login already sets these; the gateway
        // must match or a gateway-issued token defaults to channel 1.
        if ($channelId !== null) {
            $claims['channel_id'] = $channelId;
        }
        if ($inventorySourceId !== null) {
            $claims['inventory_source_id'] = $inventorySourceId;
        }

        return $this->jwt->issue($claims);
    }

    protected function extractBearer(Request $req): ?string
    {
        $h = (string) $req->header('Authorization', '');
        if (! str_starts_with($h, 'Bearer ')) {
            return null;
        }
        $tok = trim(substr($h, 7));
        return $tok !== '' ? $tok : null;
    }
}
