<?php

namespace Fujiba\LiveChat\Http\Controllers\Shop;

use App\Models\LoginToken;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Fujiba\LiveChat\Services\MessengerAutoLoginService;

class MessengerLoginController extends Controller
{
    protected MessengerAutoLoginService $loginService;

    public function __construct(MessengerAutoLoginService $loginService)
    {
        $this->loginService = $loginService;
    }

    /**
     * GET /messenger/login?subscriber_id=xxx&page_id=71&redirect=/checkout
     * Called from Chatpion bot button / webview
     */
    public function login(Request $request)
    {
        $subscriberId = $request->query('subscriber_id');
        $pageId       = $request->query('page_id');
        $redirect     = $request->query('redirect', '/');

        if (!$subscriberId || !$pageId) {
            return view('fujiba_livechat::shop.messenger-login', [
                'error' => 'Missing subscriber_id or page_id',
            ]);
        }

        $result = $this->loginService->loginBySubscriberId($subscriberId, $pageId);

        if (!$result['success']) {
            return view('fujiba_livechat::shop.messenger-login', [
                'error' => $result['error'],
            ]);
        }

        [, $rawToken] = LoginToken::issue(
            (int) $result['customer']->id,
            10,
            $request->ip(),
            $request->userAgent()
        );

        // Default to the Blade storefront exchange. If the bot explicitly
        // sent redirect=/pwa/... we honour the PWA flow instead.
        if ($redirect && str_starts_with($redirect, '/pwa')) {
            $url = $redirect . (str_contains($redirect, '?') ? '&' : '?')
                 . 'token=' . urlencode($rawToken);
            return redirect()->to($url);
        }

        $url = route('messenger.exchange', array_filter([
            'token'  => $rawToken,
            'target' => $redirect && $redirect !== '/' ? ltrim($redirect, '/') : null,
        ]));

        return redirect()->to($url);
    }

    /**
     * GET /messenger/status — JSON health check
     */
    public function status(Request $request)
    {
        $subscriberId = $request->query('subscriber_id');
        $customer     = auth()->guard('customer')->user();

        return response()->json([
            'logged_in'     => $customer !== null,
            'subscriber_id' => $subscriberId,
            'customer_id'   => $customer?->id,
            'email'         => $customer?->email,
        ]);
    }
}
