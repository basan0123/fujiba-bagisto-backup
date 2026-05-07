<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class SetTenantStorageDisk
{
    public function handle(Request $request, Closure $next)
    {
        if (tenancy()->initialized && tenancy()->tenant) {
            $tenantId  = tenancy()->tenant->getTenantKey();
            $channelId = $this->resolveChannelId();

            // Layout: storage/app/public/tenants/{channel_id}/{tenant_uuid}
            //
            // Historic data was written under channel_id=1 regardless of the
            // product's actual channel (the per-channel split predates
            // the data). For multi-channel tenants whose non-default channel
            // directory has no real assets, fall back to channel 1 so legacy
            // image URLs still resolve.
            $effectiveChannelId = $this->resolveEffectiveChannelId($tenantId, $channelId);

            $tenantRoot = storage_path("app/public/tenants/{$effectiveChannelId}/{$tenantId}");
            $tenantUrl  = $request->getSchemeAndHttpHost() . "/storage/tenants/{$effectiveChannelId}/{$tenantId}";

            // Override the public disk at runtime
            Config::set('filesystems.disks.public.root', $tenantRoot);
            Config::set('filesystems.disks.public.url', $tenantUrl);

            // Purge cached disk instance so new config takes effect
            Storage::forgetDisk('public');

            // Create directory scaffold if it doesn't exist
            if (! is_dir($tenantRoot)) {
                foreach (['', '/product', '/category', '/velocity', '/theme'] as $sub) {
                    $dir = $tenantRoot . $sub;
                    if (! is_dir($dir)) {
                        mkdir($dir, 0775, true);
                    }
                }

                @chown($tenantRoot, 'www-data');
            }
        }

        return $next($request);
    }

    /**
     * If the tenant has no real product/category data under the requested
     * channel directory but does under channel 1, prefer channel 1.
     * This preserves backwards compatibility with the original layout where
     * all of a tenant's assets lived under tenants/1/{uuid}/ regardless of
     * the product's channel.
     */
    protected function resolveEffectiveChannelId(string $tenantId, int $channelId): int
    {
        if ($channelId === 1) {
            return 1;
        }

        $requested = storage_path("app/public/tenants/{$channelId}/{$tenantId}/product");

        if (is_dir($requested)) {
            // Heuristic: real content vs scaffold-only.
            $entries = @scandir($requested) ?: [];
            $hasContent = collect($entries)->contains(fn ($e) => $e !== '.' && $e !== '..');

            if ($hasContent) {
                return $channelId;
            }
        }

        $fallback = storage_path("app/public/tenants/1/{$tenantId}");

        if (is_dir($fallback)) {
            return 1;
        }

        return $channelId;
    }

    /**
     * Resolve the channel_id for the current tenant context.
     *
     * For POS JWT requests the channel_id is in the token payload.
     * For admin subdomain requests we fall back to the tenant's first/default channel.
     */
    protected function resolveChannelId(): int
    {
        // Try JWT payload first (set by ValidatePosJwtTenant middleware)
        try {
            $payload = app(\PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::class)::parseToken()->getPayload();
            if ($payload && $payload->get('channel_id')) {
                return (int) $payload->get('channel_id');
            }
        } catch (\Throwable) {
            // No JWT in request — fall through to DB lookup
        }

        // Fallback: use the tenant DB's first channel
        try {
            return (int) (\DB::table('channels')->orderBy('id')->value('id') ?? 1);
        } catch (\Throwable) {
            return 1;
        }
    }
}
