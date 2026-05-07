<?php

namespace Fujiba\MessengerBridge\Http\Controllers;

use App\Models\LoginToken;
use Fujiba\MessengerBridge\Services\BridgeLogger;
use Fujiba\MessengerBridge\Services\ChannelMapper;
use Fujiba\MessengerBridge\Services\CustomerResolver;
use Fujiba\MessengerBridge\Services\NonceStore;
use Fujiba\MessengerBridge\Services\TokenVerifier;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * GET /messenger-bridge?t=<signed-token>&r=<relative-path>
 *
 * Verifies an HMAC-signed token from ChatPion, resolves the matching
 * Bagisto customer for the channel, then hands off to the existing
 * messenger.exchange flow which mints the session.
 *
 * The ChatPion side must:
 *   - sign the payload with MESSENGER_BRIDGE_SECRET
 *   - point the redirect at the channel's correct host
 *     (mmfood.fujiba.co.jp for ch=9; kimono.fujiba.co.jp for ch=10)
 *
 * On any failure: log + 302 to channel home with a translated flash.
 * Never reveal which check failed in the user-facing message.
 */
class MessengerBridgeController extends Controller
{
    public function __construct(
        private TokenVerifier    $verifier,
        private CustomerResolver $resolver,
        private NonceStore       $nonces,
        private BridgeLogger     $logger,
    ) {}

    public function __invoke(Request $request)
    {
        $token       = (string) $request->query('t', '');
        $relativeRaw = (string) $request->query('r', '');
        $ip          = (string) $request->ip();
        $ua          = (string) $request->userAgent();

        if ($token === '') {
            $this->logger->fail('missing_token', null, null, $ip, $ua);
            return $this->failRedirect($request);
        }

        $verifyResult = $this->verifier->verify($token);
        if (! $verifyResult['ok']) {
            $this->logger->fail($verifyResult['reason'], null, null, $ip, $ua);
            return $this->failRedirect($request);
        }

        $payload = $verifyResult['payload'];
        $sub     = $payload['sub'];
        $ch      = $payload['ch'];
        $nce     = $payload['nce'];

        // Strict per-channel host check — mmfood token MUST hit mmfood host.
        $expectedHost = ChannelMapper::expectedHost($ch);
        if ($expectedHost === null || strcasecmp($request->getHost(), $expectedHost) !== 0) {
            $this->logger->fail('host_mismatch', $sub, $ch, $ip, $ua, [
                'got_host'      => $request->getHost(),
                'expected_host' => $expectedHost,
            ]);
            return $this->failRedirect($request);
        }

        // Replay defense — record nonce; duplicate = reject.
        if (! $this->nonces->recordOrReject($nce)) {
            $this->logger->fail('replay', $sub, $ch, $ip, $ua, ['nce' => $nce]);
            return $this->failRedirect($request);
        }

        $bagistoChannelId = ChannelMapper::bagistoChannelId($ch);
        $customer = $this->resolver->resolve($sub, $bagistoChannelId);

        if (! $customer) {
            $this->logger->fail('customer_not_found', $sub, $ch, $ip, $ua);
            return $this->failRedirect($request, 'no_customer');
        }

        // Mint a one-time exchange token (10-minute TTL, the existing default
        // used by MessengerLoginController) and hand off.
        [, $rawToken] = LoginToken::issue(
            (int) $customer->id,
            10,
            $ip,
            $ua,
        );

        $this->logger->ok($sub, $ch, (int) $customer->id, $ip, $ua);

        $target = $this->safeRelativePath($relativeRaw);

        return redirect()->to(route('messenger.exchange', array_filter([
            'token'  => $rawToken,
            'target' => $target,
        ])));
    }

    /**
     * Sanitize the optional 'r' parameter. Only relative paths are allowed:
     * - must start with /
     * - must NOT start with // (protocol-relative open redirect)
     * - must NOT start with /\ (Windows-style)
     * - must NOT contain a scheme (':' before any /)
     * - must be under 512 chars
     *
     * Returns null if rejected (caller falls back to channel home).
     */
    private function safeRelativePath(string $r): ?string
    {
        if ($r === '') return null;
        if (strlen($r) > 512) return null;
        if ($r[0] !== '/') return null;
        if (str_starts_with($r, '//')) return null;
        if (str_starts_with($r, '/\\')) return null;
        $colon = strpos($r, ':');
        $slash = strpos($r, '/');
        if ($colon !== false && ($slash === false || $colon < $slash)) return null;
        return $r;
    }

    /**
     * Always returns the visitor to the channel home with a translated
     * flash. The reason key controls the specific message; default is the
     * generic "link expired or invalid" copy.
     */
    private function failRedirect(Request $request, string $reasonKey = 'invalid_link')
    {
        $message = trans("messenger-bridge::app.{$reasonKey}");
        // Fallback if translation missing (failed-closed never fails open).
        if ($message === "messenger-bridge::app.{$reasonKey}") {
            $message = trans('messenger-bridge::app.invalid_link');
        }

        return redirect()->to('/')->with('warning', $message);
    }
}
