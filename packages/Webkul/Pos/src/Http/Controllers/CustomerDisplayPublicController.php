<?php

namespace Webkul\Pos\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\Pos\Models\CustomerDisplaySession;

class CustomerDisplayPublicController extends Controller
{
    private const POLL_INTERVAL_US = 1_000_000;

    private const KEEPALIVE_SECONDS = 7;

    private const MAX_STREAM_SECONDS = 1800;

    public function show(\Illuminate\Http\Request $request, string $token)
    {
        $row = $this->resolveActive($token);
        abort_unless($row, 404);

        $updates = ['last_seen_at' => now()];

        if (! $row->paired_device_label) {
            $ua = (string) $request->userAgent();
            $updates['paired_device_label'] = self::deriveLabel($ua);
            $updates['paired_device_ua']    = mb_substr($ua, 0, 500);
            $updates['paired_device_ip']    = $request->ip();
        }

        $row->update($updates);

        return response()
            ->view('pos::customer-display.show', ['token' => $token])
            ->header('Cache-Control', 'no-store');
    }

    private static function deriveLabel(string $ua): string
    {
        $os = match (true) {
            str_contains($ua, 'iPad')              => 'iPad',
            str_contains($ua, 'iPhone')            => 'iPhone',
            str_contains($ua, 'Android')           => 'Android',
            str_contains($ua, 'Mac OS X')          => 'Mac',
            str_contains($ua, 'Windows')           => 'Windows',
            default                                => 'Device',
        };

        $browser = match (true) {
            str_contains($ua, 'CriOS') || str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'FxiOS') || str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'EdgiOS') || str_contains($ua, 'Edg/')   => 'Edge',
            str_contains($ua, 'Safari') => 'Safari',
            default                     => 'Browser',
        };

        return "{$os} · {$browser}";
    }

    public function ping(string $token)
    {
        $row = $this->resolveActive($token);
        abort_unless($row, 404);

        $row->update(['last_seen_at' => now()]);

        return response()->noContent();
    }

    /**
     * Beacon-friendly revoke. Tokens themselves are the credentials;
     * accepts a JSON body of {"tokens":[...]} or a comma-separated form field.
     * Public route — no JWT, since sendBeacon cannot set Authorization headers.
     */
    public function beaconRevoke(Request $request)
    {
        $tokens = $request->input('tokens', []);

        if (is_string($tokens)) {
            $tokens = array_filter(array_map('trim', explode(',', $tokens)));
        }

        if (empty($tokens) || ! is_array($tokens)) {
            return response()->noContent();
        }

        $tokens = array_slice(array_filter($tokens, 'is_string'), 0, 20);

        CustomerDisplaySession::whereIn('pairing_token', $tokens)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at'     => now(),
                'revoked_reason' => 'beacon',
            ]);

        return response()->noContent();
    }

    public function stream(Request $request, string $token): StreamedResponse
    {
        $row = $this->resolveActive($token);
        abort_unless($row, 404);
        abort_unless($row->tenant_id === tenant()?->id, 403);

        $sessionId = $row->id;
        $lastEventId = (int) $request->header('Last-Event-ID', 0);

        return response()->stream(function () use ($sessionId, $lastEventId) {
            @set_time_limit(0);
            @ini_set('zlib.output_compression', '0');
            @ini_set('output_buffering', '0');
            @ini_set('implicit_flush', '1');
            ignore_user_abort(false);

            echo "retry: 3000\n\n";
            $this->flush();

            $cursor = $lastEventId;
            $startedAt = time();
            $lastKeepalive = time();

            while (true) {
                if (connection_aborted()) {
                    return;
                }

                if ((time() - $startedAt) >= self::MAX_STREAM_SECONDS) {
                    echo "event: rotate\ndata: {}\n\n";
                    $this->flush();
                    return;
                }

                $row = CustomerDisplaySession::find($sessionId);

                if (! $row || $row->revoked_at !== null) {
                    echo "event: revoked\ndata: {}\n\n";
                    $this->flush();
                    return;
                }

                $payloadTs = $row->last_payload_at?->getTimestampMs() ?? 0;

                if ($payloadTs > $cursor && $row->last_payload) {
                    $cursor = $payloadTs;
                    echo "id: {$cursor}\n";
                    echo "event: cart\n";
                    echo 'data: '.json_encode($row->last_payload)."\n\n";
                    $this->flush();
                    $lastKeepalive = time();
                } elseif ((time() - $lastKeepalive) >= self::KEEPALIVE_SECONDS) {
                    // Real "ping" event (not an SSE comment) so the client's
                    // watchdog can use addEventListener('ping', ...) to confirm
                    // the connection is alive even when no cart updates arrive.
                    echo "event: ping\ndata: {}\n\n";
                    $this->flush();
                    $lastKeepalive = time();
                }

                usleep(self::POLL_INTERVAL_US);
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    private function resolveActive(string $token): ?CustomerDisplaySession
    {
        // No expires_at gate — sessions live until manually revoked.
        return CustomerDisplaySession::where('pairing_token', $token)
            ->whereNull('revoked_at')
            ->first();
    }

    private function flush(): void
    {
        if (function_exists('ob_get_level') && ob_get_level() > 0) {
            @ob_flush();
        }
        @flush();
    }
}
