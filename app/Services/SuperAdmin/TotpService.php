<?php
namespace App\Services\SuperAdmin;

/**
 * RFC 6238 TOTP implementation. SHA-1, 6-digit, 30-second period — the
 * defaults assumed by Google Authenticator / Authy / 1Password.
 *
 * Self-contained so we don't pull in another composer dep.
 */
class TotpService
{
    private const PERIOD = 30;
    private const DIGITS = 6;
    private const ALGO   = 'sha1';

    public function generateSecret(int $bytes = 20): string
    {
        return $this->base32Encode(random_bytes($bytes));
    }

    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', (string) $code);
        if (! ctype_digit($code) || strlen($code) !== self::DIGITS) {
            return false;
        }

        $time = (int) floor(time() / self::PERIOD);

        for ($offset = -$window; $offset <= $window; $offset++) {
            if (hash_equals($this->computeCode($secret, $time + $offset), $code)) {
                return true;
            }
        }

        return false;
    }

    public function provisioningUri(string $secret, string $accountName, string $issuer): string
    {
        $label = rawurlencode($issuer . ':' . $accountName);
        $params = http_build_query([
            'secret'    => $secret,
            'issuer'    => $issuer,
            'algorithm' => 'SHA1',
            'digits'    => self::DIGITS,
            'period'    => self::PERIOD,
        ]);
        return "otpauth://totp/{$label}?{$params}";
    }

    public function qrCodeUrl(string $provisioningUri): string
    {
        // Using the standard Google Charts QR fallback. If the network is
        // restricted, the secret is also displayed as text so the admin can
        // type it in manually.
        return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data='
            . rawurlencode($provisioningUri);
    }

    private function computeCode(string $secret, int $time): string
    {
        $key = $this->base32Decode($secret);
        $bin = pack('J', $time);
        $hash = hash_hmac(self::ALGO, $bin, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0xF;
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            |  (ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** self::DIGITS);
        return str_pad((string) $code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bin = '';
        for ($i = 0, $n = strlen($data); $i < $n; $i++) {
            $bin .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bin, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $out .= $alphabet[bindec($chunk)];
        }
        return $out;
    }

    private function base32Decode(string $b32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $b32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $b32));
        $bin = '';
        for ($i = 0, $n = strlen($b32); $i < $n; $i++) {
            $bin .= str_pad(decbin(strpos($alphabet, $b32[$i])), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bin, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $out .= chr(bindec($chunk));
            }
        }
        return $out;
    }
}
