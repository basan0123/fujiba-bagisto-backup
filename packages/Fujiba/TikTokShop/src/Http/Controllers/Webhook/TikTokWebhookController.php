<?php

namespace Fujiba\TikTokShop\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Fujiba\TikTokShop\Models\TikTokCredential;
use Fujiba\TikTokShop\Services\TikTokOrderImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * TikTok Shop webhook entrypoint.
 *
 * Step 3 (this commit): receives, verifies HMAC, returns 200 with no event
 * dispatch. Logs every hit for observability so TikTok's webhook subsystem
 * sees a stable 200 and won't auto-disable us.
 *
 * Step 5 will fill in handleOrderStatus / handleProductStatus.
 *
 * Tenant lookup precedes tenancy initialization. Per master prompt §9 and
 * the BackfillMessengerTokens.php precedent, Tenant::find() runs against
 * the default (central) connection when no tenancy is initialized — there
 * is no connection named 'central' in this codebase (config/tenancy.php
 * sets central_connection => 'mysql').
 */
class TikTokWebhookController extends Controller
{
    public function __construct(
        protected TikTokOrderImportService $orderImportService,
    ) {}

    public function handle(Request $request, string $tenantId): JsonResponse
    {
        // Step 1 — Tenant lookup.
        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            Log::warning('[TikTokShop] webhook tenant not found', ['tenant_id' => $tenantId]);
            return response()->json(['error' => 'not_found'], 404);
        }

        // Step 2 — Initialize tenancy (we need tenant DB to read tiktok_credentials).
        tenancy()->initialize($tenant);

        try {
            // Pre-flight log — names which DB the request landed against (master prompt §0.8).
            Log::info('[TikTokShop] webhook context', [
                'db'        => DB::connection()->getDatabaseName(),
                'tenant'    => function_exists('tenant') && tenant() ? tenant('id') : 'central',
                'event'     => $request->input('type'),
                'shop_id'   => $request->input('shop_id'),
            ]);

            // Step 3 — Read app_secret from tenant DB, verify HMAC.
            // Query on non-encrypted column only (is_active) — encrypted columns
            // can never be matched by WHERE per master prompt §0.11.
            $creds  = TikTokCredential::where('is_active', true)->first();
            $secret = $creds?->tiktok_app_secret;  // decrypted via model cast

            $expected = hash_hmac('sha256', $request->getContent(), $secret ?? '');
            $given    = (string) $request->header('x-tiktok-signature', '');

            if (! $secret || ! hash_equals($expected, $given)) {
                Log::warning('[TikTokShop] webhook HMAC mismatch', [
                    'tenant_id' => $tenantId,
                    'has_creds' => $creds !== null,
                    'has_sig'   => $given !== '',
                ]);
                return response()->json(['error' => 'invalid_signature'], 401);
            }

            // Step 4 — Dispatch.
            $type = (string) $request->input('type', '');
            $data = (array)  $request->input('data', []);

            match ($type) {
                'ORDER_STATUS_CHANGE'   => $this->handleOrderStatus($data, (int) $creds->channel_id),
                'PRODUCT_STATUS_CHANGE' => $this->handleProductStatus($data),
                default                 => Log::info('[TikTokShop] webhook unknown type accepted', ['type' => $type]),
            };

            return response()->json(['success' => true]);
        } catch (Throwable $e) {
            // Webhooks must not 500 — TikTok will retry-and-disable on persistent failure.
            // Log and ack 200 so the platform doesn't escalate; Step 5 will add typed
            // exception handling for real failures.
            Log::error('[TikTokShop] webhook unexpected exception', [
                'tenant_id' => $tenantId,
                'message'   => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'error' => 'internal'], 200);
        } finally {
            tenancy()->end();
        }
    }

    /**
     * Handle ORDER_STATUS_CHANGE webhook.
     *
     * Step 5 scope: when TikTok announces a new order (any status — they fire
     * ORDER_STATUS_CHANGE both for new orders AND for status transitions on
     * existing orders), import it via TikTokOrderImportService. The import
     * service is idempotent — duplicate webhooks for the same tiktok_order_id
     * return the existing map row without creating a second Bagisto order.
     *
     * Status-transition handling (cancel → restock, shipped → mark fulfilled,
     * etc.) lives in a follow-up step. This step nails the create path.
     */
    private function handleOrderStatus(array $data, int $channelId): void
    {
        Log::info('[TikTokShop] ORDER_STATUS_CHANGE dispatching to importer', [
            'tiktok_order_id' => $data['order_id'] ?? null,
            'status'          => $data['order_status'] ?? null,
            'channel_id'      => $channelId,
        ]);

        $this->orderImportService->import($channelId, $data);
    }

    /**
     * Step 5 will: parse data, look up tiktok_product_map, sync product
     * activation/deactivation back to Bagisto.
     */
    private function handleProductStatus(array $data): void
    {
        Log::info('[TikTokShop] PRODUCT_STATUS_CHANGE received (Step 3 no-op)', [
            'product_id' => $data['product_id'] ?? null,
            'status'     => $data['product_status'] ?? null,
        ]);
    }
}
