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
            if (tenancy()->initialized && tenancy()->tenant?->getTenantKey() === $tenantId) {
                $this->bindChannelFromJwt($channelId);

                return $next($request);
            }

            $tenant = Tenant::find($tenantId);

            if (! $tenant) {
                Log::warning('ValidatePosJwtTenant: tenant not found', ['tenant_id' => $tenantId]);
                return response()->json(['error' => 'tenant_not_found'], 401);
            }

            tenancy()->initialize($tenant);

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
