<?php

namespace Fujiba\AuthGateway\Services;

use Fujiba\AuthGateway\Exceptions\InvalidJwtException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Self-contained JWT issuer + verifier for the gateway.
 *
 * Phase 1 issues HS256 tokens signed with the same shared secret the existing
 * tenant-side ValidatePosJwtTenant middleware (PHPOpenSourceSaver\JWTAuth)
 * already accepts. That keeps the tenant rollout zero-touch.
 *
 * The token shape mirrors what tenant /api/v1/pos/login already issues:
 *   { sub, tenant_id, channel_id?, email, role?, iat, exp, nbf, iss, jti }
 *
 * Phase 2 will switch to RS256 + key rotation. The class API is unchanged
 * across the algorithm switch — only `algorithm` + key material differ.
 */
class JwtService
{
    public function __construct(
        protected string $algorithm,
        protected string $secret,
        protected string $issuer,
        protected string $audience,
        protected string $kid,
        protected int    $ttlSeconds
    ) {
        if (! in_array($this->algorithm, ['HS256', 'RS256'], true)) {
            throw new \RuntimeException("JwtService supports HS256 or RS256, got {$this->algorithm}");
        }
        if ($this->algorithm === 'HS256' && strlen($this->secret) < 32) {
            throw new \RuntimeException('HS256 secret must be at least 32 chars.');
        }
    }

    /**
     * Issue a token. Returns ['jwt' => '...', 'jti' => '...', 'exp' => 1234, 'iat' => 1234].
     */
    public function issue(array $extraClaims): array
    {
        $now = time();
        $exp = $now + $this->ttlSeconds;
        $jti = (string) Str::uuid();

        $header = [
            'alg' => $this->algorithm,
            'typ' => 'JWT',
            'kid' => $this->kid,
        ];

        $claims = array_merge([
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $exp,
            'jti' => $jti,
        ], $extraClaims);

        $segments = [
            $this->b64u(json_encode($header, JSON_UNESCAPED_SLASHES)),
            $this->b64u(json_encode($claims, JSON_UNESCAPED_SLASHES)),
        ];
        $signingInput = implode('.', $segments);

        $signature = $this->algorithm === 'HS256'
            ? hash_hmac('sha256', $signingInput, $this->secret, true)
            : $this->signRs256($signingInput);

        $segments[] = $this->b64u($signature);

        return [
            'jwt' => implode('.', $segments),
            'jti' => $jti,
            'iat' => $now,
            'exp' => $exp,
        ];
    }

    /**
     * Verify a token and return the decoded claims. Throws InvalidJwtException
     * on any failure.
     */
    public function verify(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new InvalidJwtException('Malformed token.');
        }
        [$h64, $p64, $s64] = $parts;

        $header = json_decode($this->b64uDecode($h64), true);
        if (! is_array($header)) {
            throw new InvalidJwtException('Invalid header.');
        }
        $alg = $header['alg'] ?? null;
        if ($alg !== $this->algorithm) {
            // Defends against alg=none / alg-confusion attacks.
            throw new InvalidJwtException("Algorithm mismatch (expected {$this->algorithm}, got {$alg}).");
        }

        $payload = json_decode($this->b64uDecode($p64), true);
        if (! is_array($payload)) {
            throw new InvalidJwtException('Invalid payload.');
        }
        $signature = $this->b64uDecode($s64);
        $signingInput = $h64.'.'.$p64;

        $sigOk = $this->algorithm === 'HS256'
            ? hash_equals(hash_hmac('sha256', $signingInput, $this->secret, true), $signature)
            : $this->verifyRs256($signingInput, $signature);

        if (! $sigOk) {
            throw new InvalidJwtException('Bad signature.');
        }

        $now = time();
        if (isset($payload['exp']) && $payload['exp'] < $now) {
            throw new InvalidJwtException('Token expired.');
        }
        if (isset($payload['nbf']) && $payload['nbf'] > $now) {
            throw new InvalidJwtException('Token not yet valid.');
        }
        if (($payload['iss'] ?? null) !== $this->issuer) {
            throw new InvalidJwtException('Wrong issuer.');
        }

        $jti = $payload['jti'] ?? null;
        if ($jti && Cache::has($this->revokeKey($jti))) {
            throw new InvalidJwtException('Token revoked.');
        }

        return $payload;
    }

    public function revoke(string $jti, int $exp): void
    {
        $ttl = max(1, $exp - time());
        Cache::put($this->revokeKey($jti), true, $ttl);
    }

    public function expectedAlgorithm(): string
    {
        return $this->algorithm;
    }

    protected function revokeKey(string $jti): string
    {
        return "gateway:revoked:{$jti}";
    }

    protected function b64u(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    protected function b64uDecode(string $b64u): string
    {
        $pad = strlen($b64u) % 4;
        if ($pad) {
            $b64u .= str_repeat('=', 4 - $pad);
        }
        $raw = base64_decode(strtr($b64u, '-_', '+/'), true);
        if ($raw === false) {
            throw new InvalidJwtException('Bad base64 segment.');
        }
        return $raw;
    }

    /* RS256 path is wired but not used in Phase 1. Reserved for Phase 2. */
    protected function signRs256(string $input): string
    {
        $pk = openssl_pkey_get_private($this->secret); // secret == private key PEM in RS256 mode
        if (! $pk) {
            throw new \RuntimeException('Failed to load gateway JWT private key: '.openssl_error_string());
        }
        $sig = '';
        if (! openssl_sign($input, $sig, $pk, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Failed to sign JWT: '.openssl_error_string());
        }
        return $sig;
    }

    protected function verifyRs256(string $input, string $signature): bool
    {
        // Public key must be passed via secret in RS256 verify mode.
        $pub = openssl_pkey_get_public($this->secret);
        if (! $pub) {
            return false;
        }
        return openssl_verify($input, $signature, $pub, OPENSSL_ALGO_SHA256) === 1;
    }
}
