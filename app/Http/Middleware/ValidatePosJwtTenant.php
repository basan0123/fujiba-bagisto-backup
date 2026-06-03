<?php

namespace App\Http\Middleware;

use App\Http\Middleware\SetTenantStorageDisk;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ValidatePosJwtTenant
{
    public function handle(Request $request, Closure $next)
    {
        auth()->shouldUse('pos');

        $rawToken = JWTAuth::getToken();

        if (! $rawToken) {
            return $next($request);
        }

        // Drop any user memoized on this warm php-fpm worker from a prior
        // request up front, so no stale agent can be observed before we
        // re-resolve against the correct tenant DB below (resolvePosAgent).
        auth('pos')->forgetUser();

        try {
            $payload  = JWTAuth::getPayload($rawToken);

            // Support both our new 'tenant_id' claim and the legacy 'tid' claim.
            $tenantId  = $payload->get('tenant_id') ?? $payload->get('tid');
            $channelId = $payload->get('channel_id');

            if (! $tenantId) {
                return $next($request);
            }

            // Bind the JWT-claimed channel to core() whenever we already have the
            // right tenant active. InitializeTenancyForSubdomains runs as global
            // middleware and on bare fujiba.co.jp falls back to the FIRST channel
            // (kimono) — which is wrong for an mmfood agent. The JWT carries the
            // agent's actual channel, so prefer it.
            //
            // This is the normal path for Web POS on a tenant subdomain (tenancy
            // already correctly initialized by InitializeTenancyForSubdomains) —
            // its lifecycle is owned upstream, so we do NOT end it here.
            if (tenancy()->initialized && tenancy()->tenant?->getTenantKey() === $tenantId) {
                $this->resolvePosAgent($rawToken);
                $this->bindChannelFromJwt($channelId);

                return $next($request);
            }

            $tenant = Tenant::find($tenantId);

            if (! $tenant) {
                Log::warning('ValidatePosJwtTenant: tenant not found', ['tenant_id' => $tenantId]);
                return response()->json(['error' => 'tenant_not_found'], 401);
            }

            // Clear any tenancy left initialized for a DIFFERENT tenant before we
            // switch. On a warm worker a prior POS request (different tenant on
            // the same shared host) can leave tenancy initialized; initializing
            // on top of it would resolve the new tenant's models against the old
            // tenant's connection. Ending first guarantees a clean switch. (When
            // nothing is initialized — the common case — this is a no-op.)
            if (tenancy()->initialized) {
                tenancy()->end();
            }

            tenancy()->initialize($tenant);

            // Now that the correct tenant DB is active, resolve THIS request's
            // agent from the JWT's sub claim against the tenant connection and
            // pin it on the guard. Without an explicit setUser the guard's lazy
            // user() can resolve/memoize against a stale connection or a warm-
            // worker cached user (the cross-request agent bleed). Done here,
            // after tenancy switch, so retrieveById() hits the right DB.
            $this->resolvePosAgent($rawToken);

            $this->bindChannelFromJwt($channelId);

            // SetTenantStorageDisk runs as global middleware BEFORE this middleware
            // initializes the tenant from the JWT, so the disk re-pointing was a no-op
            // at that point (tenancy not yet active). Re-run it now so Storage::disk('public')
            // resolves product image URLs from tenants/{channel_id}/{uuid}/ instead of
            // the bare /storage/ path.
            app(SetTenantStorageDisk::class)->handle($request, fn ($r) => $r);

            try {
                return $next($request);
            } finally {
                tenancy()->end();
            }

        } catch (\Throwable $e) {
            Log::warning('ValidatePosJwtTenant: invalid token', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'invalid_token'], 401);
        }
    }

    /**
     * Resolve the POS agent for THIS request's token against the (already
     * initialized) tenant DB and pin it on the guard. Forgetting first drops
     * any user memoized on a warm php-fpm worker from a prior request; pinning
     * the token + explicit authenticate() forces re-resolution from the current
     * JWT's sub claim against the active tenant connection. This is what stops
     * the cross-request agent bleed; it does not change tenant/channel routing.
     */
    private function resolvePosAgent(string $rawToken): void
    {
        try {
            auth('pos')->forgetUser();
            auth('pos')->setToken($rawToken);
            auth('pos')->authenticate();
        } catch (\Throwable $e) {
            // Leave the guard with no user; @guard(with:["pos"]) / auth checks
            // downstream will reject as unauthenticated rather than serve a
            // stale agent.
            auth('pos')->forgetUser();
        }
    }

    private function bindChannelFromJwt(?int $channelId): void
    {
        if (! $channelId) {
            return;
        }

        $channel = \Webkul\Core\Models\Channel::find($channelId);

        if ($channel) {
            core()->setCurrentChannel($channel);
        }
    }
}
