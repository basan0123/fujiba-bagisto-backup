<?php

namespace Fujiba\TikTokShop\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Scheduled TikTok jobs are dispatched for every tenant. Tenants that never
 * ran the TikTokShop migrations have no tiktok_credentials table, so the job
 * threw on every run (~435 failed_jobs/day). Call after tenancy()->initialize():
 * returns false — and logs at most once per tenant/job/day — when TikTok is
 * not set up, so the job can return cleanly instead of failing.
 */
class TenantGuard
{
    public static function configured(string $tenantId, string $job): bool
    {
        if (Schema::hasTable('tiktok_credentials')) {
            return true;
        }

        if (Cache::add("tiktokshop:skip:{$job}:{$tenantId}:".now()->format('Ymd'), 1, now()->addDay())) {
            Log::info('[TikTokShop] tenant has no TikTok setup (tiktok_credentials missing), skipping', [
                'tenant_id' => $tenantId,
                'job'       => $job,
            ]);
        }

        return false;
    }
}
