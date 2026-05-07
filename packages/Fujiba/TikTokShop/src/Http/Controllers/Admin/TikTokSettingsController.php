<?php

namespace Fujiba\TikTokShop\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Fujiba\TikTokShop\Models\TikTokCredential;
use Fujiba\TikTokShop\Models\TikTokProductMap;
use Fujiba\TikTokShop\Services\TikTokAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;
use Webkul\Core\Models\Channel;

class TikTokSettingsController extends Controller
{
    public function __construct(
        protected TikTokAuthService $authService,
    ) {}

    /**
     * /admin/tiktok — connection-status table per channel.
     */
    public function index()
    {
        // Direct query — do not use cached repositories per master prompt §0.9.
        // Load full model: ->name is a translated accessor (channel_translations join), not a column.
        $channels = Channel::all();

        $credentialsByChannel = TikTokCredential::all()->keyBy('channel_id');

        // Per-channel sync stats (Step 8 polish item 1).
        // Single GROUP BY query, then index by channel_id for O(1) view lookup.
        $statsRows = TikTokProductMap::select(
            'channel_id',
            'sync_status',
            DB::raw('COUNT(*) as cnt'),
            DB::raw('MAX(last_synced_at) as last_synced_at')
        )
            ->groupBy('channel_id', 'sync_status')
            ->get();

        $syncStats = [];
        foreach ($statsRows as $row) {
            $cid = (int) $row->channel_id;
            if (! isset($syncStats[$cid])) {
                $syncStats[$cid] = ['synced' => 0, 'failed' => 0, 'pending' => 0, 'last_synced_at' => null];
            }
            $syncStats[$cid][$row->sync_status] = (int) $row->cnt;
            if ($row->last_synced_at) {
                $rowTs = \Carbon\Carbon::parse($row->last_synced_at);
                if (! $syncStats[$cid]['last_synced_at'] || $rowTs->gt($syncStats[$cid]['last_synced_at'])) {
                    $syncStats[$cid]['last_synced_at'] = $rowTs;
                }
            }
        }

        // Webhook URL surfaced per channel for admin to paste into TikTok dev console.
        // Tenant resolved by request domain via stancl/tenancy — we just need the id.
        $tenantId = function_exists('tenant') && tenant() ? (string) tenant('id') : null;
        $webhookUrl = $tenantId
            ? url('/webhook/tiktok/' . $tenantId)
            : null;

        return view('tiktok::admin.tiktok.index', [
            'channels'             => $channels,
            'credentialsByChannel' => $credentialsByChannel,
            'syncStats'            => $syncStats,
            'webhookUrl'           => $webhookUrl,
            'categoryIdMissing'    => empty(config('fujiba-tiktok.default_category_id')),
        ]);
    }

    /**
     * /admin/tiktok/connect?channel_id=X
     * Generates an OAuth state, stores admin+channel server-side, and
     * redirects the admin's browser to TikTok's authorize URL.
     */
    public function connect(Request $request)
    {
        $channelId = (int) $request->query('channel_id', 0);
        if ($channelId <= 0 || ! Channel::where('id', $channelId)->exists()) {
            return redirect()->route('admin.tiktok.index')
                ->with('warning', 'Invalid channel_id.');
        }

        $adminId = (int) Auth::guard('admin')->id();
        if ($adminId <= 0) {
            return redirect()->route('admin.session.create')
                ->with('warning', 'Admin session required.');
        }

        try {
            $url = $this->authService->buildAuthorizeUrl($adminId, $channelId);
        } catch (RuntimeException $e) {
            return redirect()->route('admin.tiktok.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->away($url);
    }

    /**
     * /admin/tiktok/callback?code=...&state=...
     * Exchange code → tokens, persist (encrypted) on tiktok_credentials.
     */
    public function callback(Request $request)
    {
        $code  = (string) $request->query('code', '');
        $state = (string) $request->query('state', '');

        if ($code === '' || $state === '') {
            return redirect()->route('admin.tiktok.index')
                ->with('error', 'Missing code/state on TikTok callback.');
        }

        $stateData = $this->authService->consumeState($state);
        if (! $stateData) {
            return redirect()->route('admin.tiktok.index')
                ->with('error', 'OAuth state invalid or expired. Please retry the connection.');
        }

        try {
            $payload = $this->authService->exchangeCode($code, $stateData['channel_id']);
            $creds   = $this->authService->persistTokens($stateData['channel_id'], $payload);
        } catch (Throwable $e) {
            Log::warning('[TikTokShop] callback failure', [
                'channel_id' => $stateData['channel_id'],
                'message'    => $e->getMessage(),
            ]);
            return redirect()->route('admin.tiktok.index')
                ->with('error', 'TikTok connection failed: ' . $e->getMessage());
        }

        return redirect()->route('admin.tiktok.index')
            ->with('success', 'TikTok Shop connected for channel #' . $creds->channel_id . '.');
    }

    /**
     * POST /admin/tiktok/credentials
     * Body: { channel_id, tiktok_app_key, tiktok_app_secret, redirect_uri }
     *
     * Sprint 3 — per-channel credentials. Upserts the tiktok_credentials row
     * for the given channel. The encrypted cast on tiktok_app_secret means
     * the secret is encrypted at rest automatically.
     *
     * Empty tiktok_app_secret in the form means "leave existing secret
     * untouched" — admins editing the form to change ONLY the app_key
     * shouldn't be forced to re-enter the secret. The app_key is treated
     * the same way (empty = leave existing), since both fields are
     * sensitive enough to want to avoid leaking via accidental edit.
     */
    public function saveCredentials(Request $request)
    {
        $channelId  = (int) $request->input('channel_id', 0);
        $appKey     = trim((string) $request->input('tiktok_app_key', ''));
        $appSecret  = trim((string) $request->input('tiktok_app_secret', ''));
        $redirectUri = trim((string) $request->input('redirect_uri', ''));

        if ($channelId <= 0 || ! Channel::where('id', $channelId)->exists()) {
            return redirect()->route('admin.tiktok.index')
                ->with('error', 'Invalid channel_id.');
        }

        if ($redirectUri === '' || ! filter_var($redirectUri, FILTER_VALIDATE_URL)) {
            return redirect()->route('admin.tiktok.index')
                ->with('error', 'redirect_uri is required and must be a valid URL.');
        }

        $creds = TikTokCredential::firstOrNew(['channel_id' => $channelId]);

        // Empty app_key / app_secret = "do not change". Allows admin to update
        // only the redirect_uri (or only one of the secrets) without re-typing.
        if ($appKey !== '') {
            $creds->tiktok_app_key = $appKey;
        }
        if ($appSecret !== '') {
            $creds->tiktok_app_secret = $appSecret;  // encrypted via cast
        }
        $creds->redirect_uri = $redirectUri;

        // Don't auto-flip is_active — that's reserved for OAuth completion.
        $creds->save();

        Log::info('[TikTokShop] credentials saved (manual entry)', [
            'channel_id'      => $channelId,
            'has_app_key'     => ! empty($creds->tiktok_app_key),
            'has_app_secret'  => ! empty($creds->tiktok_app_secret),
            'redirect_uri'    => $creds->redirect_uri,
        ]);

        return redirect()->route('admin.tiktok.index')
            ->with('success', 'Credentials saved for channel #' . $channelId . '. Click Connect to start OAuth.');
    }

    /**
     * /admin/tiktok/disconnect (POST) — flip is_active off for a channel.
     * Tokens remain in DB (encrypted) so the admin can re-enable without
     * a full re-OAuth if the underlying token has not yet expired.
     */
    public function disconnect(Request $request)
    {
        $channelId = (int) $request->input('channel_id', 0);
        $creds = TikTokCredential::where('channel_id', $channelId)->first();

        if ($creds) {
            $creds->is_active = false;
            $creds->save();
        }

        return redirect()->route('admin.tiktok.index')
            ->with('success', 'TikTok Shop disconnected for channel #' . $channelId . '.');
    }
}
