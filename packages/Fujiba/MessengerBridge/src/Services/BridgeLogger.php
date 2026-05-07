<?php

namespace Fujiba\MessengerBridge\Services;

use Illuminate\Support\Facades\Log;

/**
 * Wraps the dedicated 'messenger_bridge' log channel and enforces PII
 * truncation on the chatpion subscriber id before anything is written.
 */
class BridgeLogger
{
    public function ok(string $sub, int $ch, int $customerId, string $ip, string $ua): void
    {
        Log::channel('messenger_bridge')->info('OK', [
            'sub'  => self::truncateSub($sub),
            'ch'   => $ch,
            'cust' => $customerId,
            'ip'   => $ip,
            'ua'   => self::truncateUa($ua),
        ]);
    }

    public function fail(string $reason, ?string $sub, ?int $ch, string $ip, string $ua, array $extra = []): void
    {
        Log::channel('messenger_bridge')->warning('FAIL', array_merge([
            'reason' => $reason,
            'sub'    => $sub === null ? null : self::truncateSub($sub),
            'ch'     => $ch,
            'ip'     => $ip,
            'ua'     => self::truncateUa($ua),
        ], $extra));
    }

    /**
     * PSIDs are 15-17 digit numerics. Keep first 5 + last 5 so ops can
     * correlate without storing the full identifier. Short ids passthrough.
     */
    public static function truncateSub(string $sub): string
    {
        $len = strlen($sub);
        if ($len <= 11) return $sub;
        return substr($sub, 0, 5) . '...' . substr($sub, -5);
    }

    private static function truncateUa(string $ua): string
    {
        return strlen($ua) > 80 ? substr($ua, 0, 77) . '...' : $ua;
    }
}
