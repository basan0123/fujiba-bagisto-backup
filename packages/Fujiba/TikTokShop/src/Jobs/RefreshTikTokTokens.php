<?php

namespace Fujiba\TikTokShop\Jobs;

use App\Models\Tenant;
use Fujiba\TikTokShop\Models\TikTokCredential;
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
 * Refresh TikTok access tokens for one tenant whose token is within the
 * 1-hour expiry buffer.
 *
 * Schedule: every 30 minutes. Buffer: 1 hour. Token expiring at T+30m
 * gets refreshed before it expires (master prompt §7).
 *
 * Query on non-encrypted columns ONLY (is_active, token_expires_at) —
 * tiktok_app_secret / access_token / refresh_token are encrypted with
 * a random IV per write, never match in WHERE clauses (§0.11).
 */
class RefreshTikTokTokens implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public string $tenantId,
    ) {}

    public function handle(): void
    {
        $tenant = Tenant::find($this->tenantId);
        if (! $tenant) {
            Log::warning('[TikTokShop] token refresh tenant not found, dropping job', [
                'tenant_id' => $this->tenantId,
            ]);
            return;
        }

        tenancy()->initialize($tenant);

        try {
            Log::info('[TikTokShop] token refresh context', [
                'db'     => DB::connection()->getDatabaseName(),
                'tenant' => function_exists('tenant') && tenant() ? tenant('id') : 'central',
            ]);

            $candidates = TikTokCredential::where('is_active', true)
                ->whereNotNull('token_expires_at')
                ->where('token_expires_at', '<', now()->addHour())
                ->get();

            foreach ($candidates as $creds) {
                $this->refreshOne($creds);
            }
        } finally {
            tenancy()->end();
        }
    }

    private function refreshOne(TikTokCredential $creds): void
    {
        if (empty($creds->refresh_token)) {
            Log::warning('[TikTokShop] token refresh: no refresh_token, skipping', [
                'channel_id' => $creds->channel_id,
            ]);
            return;
        }

        try {
            $response = Http::asJson()->post(config('fujiba-tiktok.auth_refresh_url'), [
                'app_key'       => $creds->tiktok_app_key ?: config('fujiba-tiktok.app_key'),
                'app_secret'    => $creds->tiktok_app_secret ?: config('fujiba-tiktok.app_secret'),
                'refresh_token' => $creds->refresh_token,
                'grant_type'    => 'refresh_token',
            ]);

            if (! $response->successful()) {
                Log::warning('[TikTokShop] token refresh HTTP failure', [
                    'channel_id' => $creds->channel_id,
                    'status'     => $response->status(),
                    'body'       => substr($response->body(), 0, 500),
                ]);
                return; // leave creds unchanged; next 30-min tick retries
            }

            $payload = $response->json('data') ?? $response->json();
            if (! is_array($payload) || empty($payload['access_token'])) {
                Log::warning('[TikTokShop] token refresh returned no access_token', [
                    'channel_id' => $creds->channel_id,
                    'body'       => substr($response->body(), 0, 500),
                ]);
                return;
            }

            $expireIn = (int) ($payload['access_token_expire_in'] ?? 3600);

            $creds->access_token     = (string) $payload['access_token'];
            if (! empty($payload['refresh_token'])) {
                $creds->refresh_token = (string) $payload['refresh_token'];
            }
            $creds->token_expires_at = now()->addSeconds($expireIn);
            $creds->save();

            Log::info('[TikTokShop] token refreshed', [
                'channel_id'        => $creds->channel_id,
                'token_expires_at'  => $creds->token_expires_at->toIso8601String(),
            ]);
        } catch (Throwable $e) {
            Log::error('[TikTokShop] token refresh exception', [
                'channel_id' => $creds->channel_id,
                'message'    => $e->getMessage(),
            ]);
            // Don't rethrow — one channel's failure must not abort other
            // channels in this tenant. The scheduler retries on next tick.
        }
    }
}
