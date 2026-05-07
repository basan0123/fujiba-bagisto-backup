<?php

namespace Fujiba\AutoLogin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LoginToken;
use Fujiba\AutoLogin\Services\TikTokCustomerResolver;
use Fujiba\AutoLogin\Services\TikTokProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * TikTok customer SSO into the storefront.
 *
 * Mirrors MessengerLoginController exactly:
 *   - resolve customer by tiktok_open_id (scoped to current channel)
 *   - create one if missing, using TikTokProfileService for profile fields
 *   - issue a one-time LoginToken
 *   - redirect to messenger.exchange (the existing token-exchange endpoint
 *     in BladeAutologinController) which logs the customer into the
 *     `customer` session guard and lands them on a Blade target.
 *
 * Sprint 5 refactor (2026-05-01): the create-or-find half delegates to
 * Fujiba\AutoLogin\Services\TikTokCustomerResolver so the same logic is
 * shared with Fujiba\TikTokShop\Services\TikTokOrderImportService. The
 * resolver uses the same synthetic email format (tt_{openId}@tiktok.local)
 * and full required-fields shape Sprint 1 originally implemented inline.
 */
class TikTokLoginController extends Controller
{
    public function __construct(
        protected TikTokProfileService $profileService,
        protected TikTokCustomerResolver $resolver,
    ) {}

    public function login(Request $request)
    {
        $openId = (string) $request->query('open_id', '');
        $target = (string) $request->query('target', '');

        if ($openId === '' || ! preg_match('/^[A-Za-z0-9_\-]+$/', $openId) || strlen($openId) > 100) {
            Log::warning("TikTokLogin: invalid open_id '{$openId}'");
            return redirect()->route('shop.home.index');
        }

        $channelId = core()->getCurrentChannel()->id;

        // Profile fetch is a stub today — returns null until Sprint 2 wires
        // OAuth credentials into the TikTok profile-info endpoint. The
        // resolver handles null gracefully by using placeholder name fields.
        $profile = $this->profileService->fetch($openId);

        $customer = $this->resolver->resolveOrCreate($openId, $channelId, $profile);

        [, $rawToken] = LoginToken::issue(
            (int) $customer->id,
            10,
            $request->ip(),
            $request->userAgent()
        );

        $url = route('messenger.exchange', [
            'token'  => $rawToken,
            'target' => $target ?: null,
        ]);

        return redirect()->to($url);
    }
}
