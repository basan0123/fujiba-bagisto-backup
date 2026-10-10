<?php

namespace Fujiba\TikTokShop\Jobs;

use App\Models\Tenant;
use Fujiba\TikTokShop\Models\TikTokCredential;
use Fujiba\TikTokShop\Support\TenantGuard;
use Fujiba\TikTokShop\Services\TikTokProductService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Daily batch product sync for ONE tenant.
 *
 * Iterates the tenant's channels that have active credentials with
 * sync_products=true, then iterates products on each channel via
 * product_flat (verified Step 4 as the channel-scoped product source),
 * calling TikTokProductService::syncProduct per product.
 *
 * Per master prompt §6: one job per tenant — the scheduler sweeps via
 * Tenant::query()->chunkById(50) and dispatches one of these per tenant.
 * This job NEVER iterates Tenant::all().
 */
class SyncProductsToTikTok implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 120;

    public function __construct(
        public string $tenantId,
    ) {}

    public function handle(TikTokProductService $service): void
    {
        $tenant = Tenant::find($this->tenantId);
        if (! $tenant) {
            Log::warning('[TikTokShop] product sync tenant not found, dropping', [
                'tenant_id' => $this->tenantId,
            ]);
            return;
        }

        tenancy()->initialize($tenant);

        try {
            Log::info('[TikTokShop] scheduled product sync context', [
                'db'     => DB::connection()->getDatabaseName(),
                'tenant' => function_exists('tenant') && tenant() ? tenant('id') : 'central',
            ]);

            if (! TenantGuard::configured($this->tenantId, 'product-sync')) {
                return;
            }

            $activeChannels = TikTokCredential::where('is_active', true)
                ->where('sync_products', true)
                ->get();

            foreach ($activeChannels as $creds) {
                $this->syncOneChannel($creds, $service);
            }
        } finally {
            tenancy()->end();
        }
    }

    private function syncOneChannel(TikTokCredential $creds, TikTokProductService $service): void
    {
        $channel = \Webkul\Core\Models\Channel::find($creds->channel_id);
        if (! $channel) {
            Log::warning('[TikTokShop] scheduled sync: channel missing', ['channel_id' => $creds->channel_id]);
            return;
        }

        $productIds = DB::table('product_flat')
            ->where('channel', $channel->code)
            ->pluck('product_id')
            ->unique()
            ->values()
            ->all();

        $synced = 0; $failed = 0; $skipped = 0;
        foreach ($productIds as $pid) {
            try {
                $map = $service->syncProduct((int) $pid, (int) $creds->channel_id);
                $map->sync_status === 'synced' ? $synced++ : $failed++;
            } catch (RuntimeException $e) {
                // Hard config error (TIKTOK_DEFAULT_CATEGORY_ID empty) — abort
                // this CHANNEL's loop, not the whole tenant. Other channels in
                // the same tenant may have different category mappings later.
                Log::error('[TikTokShop] scheduled sync: channel aborted', [
                    'channel_id' => $creds->channel_id,
                    'reason'     => $e->getMessage(),
                ]);
                return;
            } catch (Throwable $e) {
                $skipped++;
                Log::warning('[TikTokShop] scheduled sync: per-product exception', [
                    'product_id' => $pid,
                    'message'    => $e->getMessage(),
                ]);
            }
        }

        $creds->last_synced_at = now();
        $creds->save();

        Log::info('[TikTokShop] scheduled sync channel complete', [
            'channel_id' => $creds->channel_id,
            'synced'     => $synced,
            'failed'     => $failed,
            'skipped'    => $skipped,
        ]);
    }
}
