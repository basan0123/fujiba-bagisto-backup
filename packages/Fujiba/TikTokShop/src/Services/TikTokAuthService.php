<?php

namespace Fujiba\TikTokShop\Services;

use Fujiba\TikTokShop\Models\TikTokCredential;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * TikTok Shop OAuth helper.
 *
 * Owns: state generation/validation, authorize-URL construction, code →
 * token exchange. Token storage lives on the TikTokCredential model
 * (encrypted via Laravel casts).
 */
class TikTokAuthService
{
    private const STATE_CACHE_PREFIX = 'fujiba.tiktok.oauth_state.';
    private const STATE_TTL_SECONDS  = 600; // 10 min — covers a slow OAuth round-trip

    /**
     * Build the URL the admin should be redirected to in order to start the
     * TikTok OAuth dance. State is single-use and bound to the calling admin
     * + channel; cached server-side so we can validate on callback without
     * trusting a query param echo.
     *
     * Sprint 3 change: app_key + redirect_uri come from the channel's
     * tiktok_credentials row, NOT from .env. Each tenant/channel uses its
     * own TikTok developer-app credentials.
     */
    public function buildAuthorizeUrl(int $adminId, int $channelId): string
    {
        $creds = TikTokCredential::where('channel_id', $channelId)->first();

        if (! $creds || empty($creds->tiktok_app_key) || empty($creds->redirect_uri)) {
            throw new RuntimeException(
                'TikTok app_key and redirect_uri must be saved for channel #' . $channelId
                . ' before connecting. Use the credentials form on /admin/tiktok.'
            );
        }

        $state = Str::random(40);
        Cache::put(
            self::STATE_CACHE_PREFIX . $state,
            ['admin_id' => $adminId, 'channel_id' => $channelId, 'created_at' => now()->toIso8601String()],
            self::STATE_TTL_SECONDS
        );

        return config('fujiba-tiktok.auth_authorize_url') . '?' . http_build_query([
            'app_key'       => $creds->tiktok_app_key,
            'redirect_uri'  => $creds->redirect_uri,
            'state'         => $state,
            'response_type' => 'code',
        ]);
    }

    /**
     * Validate state on callback and return the (admin_id, channel_id) that
     * initiated the OAuth flow. State is consumed (deleted from cache) so
     * replay attacks against a leaked redirect URL fail.
     *
     * @return array{admin_id:int,channel_id:int}|null
     */
    public function consumeState(string $state): ?array
    {
        $key  = self::STATE_CACHE_PREFIX . $state;
        $data = Cache::get($key);
        if (! $data) {
            return null;
        }
        Cache::forget($key);

        return [
            'admin_id'   => (int) ($data['admin_id'] ?? 0),
            'channel_id' => (int) ($data['channel_id'] ?? 0),
        ];
    }

    /**
     * Exchange an OAuth authorization code for an access_token + refresh_token.
     * Returns the raw response body decoded as an array. Throws on HTTP error
     * so the controller can show a friendly message.
     *
     * Sprint 3 change: app_key + app_secret come from the channel's
     * tiktok_credentials row, NOT from .env.
     *
     * @return array{access_token:string,refresh_token:string,access_token_expire_in:int,refresh_token_expire_in:int,seller_name?:string,seller_base_region?:string,open_id?:string}
     */
    public function exchangeCode(string $code, int $channelId): array
    {
        $creds = TikTokCredential::where('channel_id', $channelId)->first();
        if (! $creds || empty($creds->tiktok_app_key) || empty($creds->tiktok_app_secret)) {
            throw new RuntimeException('TikTok credentials not saved for channel #' . $channelId . '.');
        }

        $tokenUrl = config('fujiba-tiktok.auth_token_url');

        $response = Http::asJson()->post($tokenUrl, [
            'app_key'    => $creds->tiktok_app_key,
            'app_secret' => $creds->tiktok_app_secret,  // decrypted via cast
            'auth_code'  => $code,
            'grant_type' => 'authorized_code',
        ]);

        if (! $response->successful()) {
            Log::warning('[TikTokShop] token exchange HTTP failure', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new RuntimeException('TikTok token exchange failed (HTTP ' . $response->status() . ').');
        }

        $payload = $response->json('data') ?? $response->json();
        if (! is_array($payload) || empty($payload['access_token'])) {
            Log::warning('[TikTokShop] token exchange returned no access_token', ['body' => $response->body()]);
            throw new RuntimeException('TikTok token exchange returned an unexpected payload.');
        }

        return $payload;
    }

    /**
     * Persist exchange-result tokens into tiktok_credentials for the given
     * channel. Encryption happens automatically via the model's casts.
     *
     * Sprint 3 change: tiktok_app_key + tiktok_app_secret are NOT auto-filled
     * from .env. They must already be present on the row (saved via the
     * credentials form before the admin clicks Connect). If absent at this
     * point it's a flow error — buildAuthorizeUrl() should have rejected
     * earlier.
     */
    public function persistTokens(int $channelId, array $tokenPayload): TikTokCredential
    {
        $now      = now();
        $expireIn = (int) ($tokenPayload['access_token_expire_in'] ?? 3600);

        $creds = TikTokCredential::where('channel_id', $channelId)->first();
        if (! $creds) {
            throw new RuntimeException(
                'persistTokens called for channel #' . $channelId
                . ' but no tiktok_credentials row exists. The credentials form must be saved before OAuth.'
            );
        }

        $creds->access_token     = $tokenPayload['access_token'];
        $creds->refresh_token    = $tokenPayload['refresh_token'] ?? null;
        $creds->token_expires_at = $now->copy()->addSeconds($expireIn);
        $creds->is_active        = true;

        if (! empty($tokenPayload['seller_name'])) {
            $creds->tiktok_shop_name = (string) $tokenPayload['seller_name'];
        }
        if (! empty($tokenPayload['shop_id'])) {
            $creds->tiktok_shop_id = (string) $tokenPayload['shop_id'];
        } elseif (! empty($tokenPayload['open_id'])) {
            $creds->tiktok_shop_id = (string) $tokenPayload['open_id'];
        }

        $creds->save();

        Log::info('[TikTokShop] credentials saved', [
            'db'         => DB::connection()->getDatabaseName(),
            'tenant'     => function_exists('tenant') && tenant() ? tenant('id') : 'central',
            'channel_id' => $channelId,
            'shop_id'    => $creds->tiktok_shop_id,
        ]);

        return $creds;
    }
}
