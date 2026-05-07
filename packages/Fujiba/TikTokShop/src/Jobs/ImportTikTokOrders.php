<?php

namespace Fujiba\TikTokShop\Jobs;

use App\Models\Tenant;
use Fujiba\TikTokShop\Models\TikTokCredential;
use Fujiba\TikTokShop\Services\TikTokOrderImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 15-minute scheduled fallback for the webhook order-import path.
 *
 * Pulls TikTok orders in AWAITING_SHIPMENT / UNPAID status that we may
 * have missed via webhook (TikTok webhook reliability is "mostly" — the
 * scheduler is the safety net). Each pulled order is fed through the
 * same TikTokOrderImportService that the webhook controller calls, so
 * dedup via tiktok_order_map.unique('tiktok_order_id') applies the same
 * way — duplicates between webhook + scheduler are silently merged.
 *
 * Per master prompt §6: one job per tenant. Scheduler dispatches via
 * Tenant::query()->chunkById(50).
 *
 * IMPORTANT (discovery item E): this job creates Bagisto orders, which
 * fires checkout.order.save.after — the Notification package listens
 * and sends real Messenger notifications. Smoke tests must use
 * Event::fake() to suppress.
 */
class ImportTikTokOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public string $tenantId,
    ) {}

    public function handle(TikTokOrderImportService $importService): void
    {
        $tenant = Tenant::find($this->tenantId);
        if (! $tenant) {
            Log::warning('[TikTokShop] order import tenant not found, dropping', [
                'tenant_id' => $this->tenantId,
            ]);
            return;
        }

        tenancy()->initialize($tenant);

        try {
            Log::info('[TikTokShop] scheduled order import context', [
                'db'     => DB::connection()->getDatabaseName(),
                'tenant' => function_exists('tenant') && tenant() ? tenant('id') : 'central',
            ]);

            $activeChannels = TikTokCredential::where('is_active', true)
                ->where('sync_orders', true)
                ->get();

            foreach ($activeChannels as $creds) {
                $this->importOneChannel($creds, $importService);
            }
        } finally {
            tenancy()->end();
        }
    }

    private function importOneChannel(TikTokCredential $creds, TikTokOrderImportService $importService): void
    {
        if (! $creds->access_token) {
            return;
        }

        try {
            $orders = $this->fetchOrdersFromTikTok($creds);
        } catch (Throwable $e) {
            Log::error('[TikTokShop] scheduled import: fetch failed', [
                'channel_id' => $creds->channel_id,
                'message'    => $e->getMessage(),
            ]);
            return;
        }

        $imported = 0; $skipped = 0; $failed = 0;
        foreach ($orders as $orderPayload) {
            try {
                $map = $importService->import((int) $creds->channel_id, $orderPayload);
                if ($map->import_status === 'imported') {
                    $imported++;
                } elseif ($map->import_status === 'failed') {
                    $failed++;
                } else {
                    // Already-imported (dedup hit) returns the existing row
                    // unchanged — count as skipped for visibility.
                    $skipped++;
                }
            } catch (Throwable $e) {
                $failed++;
                Log::warning('[TikTokShop] scheduled import: per-order exception', [
                    'tiktok_order_id' => $orderPayload['order_id'] ?? null,
                    'message'         => $e->getMessage(),
                ]);
            }
        }

        Log::info('[TikTokShop] scheduled import channel complete', [
            'channel_id' => $creds->channel_id,
            'imported'   => $imported,
            'skipped'    => $skipped,
            'failed'     => $failed,
        ]);
    }

    /**
     * Pull pending orders from TikTok. The endpoint and pagination shape
     * need to be confirmed against TikTok JP Open Platform docs (master
     * prompt §17.3 — currently an open question). This implementation
     * uses the documented v202309 product/order namespace and assumes
     * POST with a status filter; if the JP API differs, the smoke test's
     * Http::fake() lets us correct the call site without touching the
     * import service.
     */
    private function fetchOrdersFromTikTok(TikTokCredential $creds): array
    {
        $response = Http::withToken($creds->access_token)
            ->asJson()
            ->post('https://open-api.tiktokshop.com/order/202309/orders/search', [
                'order_status' => ['AWAITING_SHIPMENT', 'UNPAID'],
                'page_size'    => 50,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('TikTok order search HTTP ' . $response->status());
        }

        $body = $response->json();
        if (($body['code'] ?? -1) !== 0) {
            throw new \RuntimeException('TikTok order search code=' . ($body['code'] ?? 'null'));
        }

        return $body['data']['orders'] ?? [];
    }
}
