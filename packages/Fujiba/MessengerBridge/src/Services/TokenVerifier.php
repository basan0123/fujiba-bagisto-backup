<?php

namespace Fujiba\MessengerBridge\Services;

/**
 * Verifies signed login tokens issued by ChatPion.
 *
 * Token format: <base64url(payload_json)>.<base64url(hmac_sha256(secret, payload_json))>
 * Payload: { sub: string, ch: int(9|10), iat: int, exp: int, nce: 6-char-hex }
 *
 * Pure crypto + parsing. No DB, no replay check (caller does that).
 */
class TokenVerifier
{
    public const ERR_MALFORMED      = 'malformed';
    public const ERR_BAD_SIG        = 'bad_sig';
    public const ERR_BAD_PAYLOAD    = 'bad_payload';
    public const ERR_EXPIRED        = 'expired';
    public const ERR_FUTURE_IAT     = 'future_iat';
    public const ERR_BAD_CHANNEL    = 'bad_channel';

    /** Allowed clock skew in seconds. iat must not be more than this in the future. */
    public const SKEW_SECONDS = 30;

    /** ChatPion store_id values that this bridge accepts. */
    public const ALLOWED_CHANNELS = [9, 10];

    /**
     * @param  string[]  $secrets  Active + previous (rotation window). First
     *                             that verifies wins. Empty strings are
     *                             skipped so PREVIOUS=empty doesn't accept
     *                             unsigned junk.
     */
    public function __construct(private array $secrets) {}

    /**
     * @return array{ok:true, payload:array}|array{ok:false, reason:string}
     */
    public function verify(string $token, ?int $now = null): array
    {
        $now ??= time();

        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return ['ok' => false, 'reason' => self::ERR_MALFORMED];
        }

        $payloadB64 = $parts[0];
        $sigB64     = $parts[1];

        $payloadRaw = self::b64urlDecode($payloadB64);
        $sigRaw     = self::b64urlDecode($sigB64);
        if ($payloadRaw === null || $sigRaw === null) {
            return ['ok' => false, 'reason' => self::ERR_MALFORMED];
        }

        // Constant-time HMAC verify against any active secret.
        $matched = false;
        foreach ($this->secrets as $secret) {
            if (! is_string($secret) || $secret === '') continue;
            $expected = hash_hmac('sha256', $payloadRaw, $secret, true);
            if (hash_equals($expected, $sigRaw)) {
                $matched = true;
                break;
            }
        }
        if (! $matched) {
            return ['ok' => false, 'reason' => self::ERR_BAD_SIG];
        }

        $payload = json_decode($payloadRaw, true);
        if (! is_array($payload)) {
            return ['ok' => false, 'reason' => self::ERR_BAD_PAYLOAD];
        }

        // Strict shape — all required, exact types.
        $sub = $payload['sub'] ?? null;
        $ch  = $payload['ch']  ?? null;
        $iat = $payload['iat'] ?? null;
        $exp = $payload['exp'] ?? null;
        $nce = $payload['nce'] ?? null;

        if (
            ! is_string($sub) || $sub === ''
            || ! is_int($ch)
            || ! is_int($iat)
            || ! is_int($exp)
            || ! is_string($nce) || ! preg_match('/^[a-f0-9]{6}$/', $nce)
        ) {
            return ['ok' => false, 'reason' => self::ERR_BAD_PAYLOAD];
        }

        if ($iat > $now + self::SKEW_SECONDS) {
            return ['ok' => false, 'reason' => self::ERR_FUTURE_IAT];
        }
        if ($exp <= $now) {
            return ['ok' => false, 'reason' => self::ERR_EXPIRED];
        }

        if (! in_array($ch, self::ALLOWED_CHANNELS, true)) {
            return ['ok' => false, 'reason' => self::ERR_BAD_CHANNEL];
        }

        return ['ok' => true, 'payload' => [
            'sub' => $sub,
            'ch'  => $ch,
            'iat' => $iat,
            'exp' => $exp,
            'nce' => $nce,
        ]];
    }

    public static function b64urlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    /** Returns null on invalid input rather than throwing. */
    public static function b64urlDecode(string $s): ?string
    {
        if ($s === '') return '';
        if (! preg_match('/^[A-Za-z0-9_-]+$/', $s)) return null;
        $padded = strtr($s, '-_', '+/');
        $pad = strlen($padded) % 4;
        if ($pad) $padded .= str_repeat('=', 4 - $pad);
        $out = base64_decode($padded, true);
        return $out === false ? null : $out;
    }

    /**
     * Sign helper — only used by the CLI signer for end-to-end tests.
     * Production signing happens on ChatPion's side, not here.
     */
    public static function sign(array $payload, string $secret): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $sig  = hash_hmac('sha256', $json, $secret, true);
        return self::b64urlEncode($json) . '.' . self::b64urlEncode($sig);
    }
}
