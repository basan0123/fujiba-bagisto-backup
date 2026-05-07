<?php

namespace Fujiba\MessengerBridge\Tests\Unit;

use Fujiba\MessengerBridge\Services\TokenVerifier;
use PHPUnit\Framework\TestCase;

class TokenVerifierTest extends TestCase
{
    private string $secret = 'test-secret-do-not-use-in-prod';

    private function validPayload(int $now): array
    {
        return [
            'sub' => '10003586742996289',
            'ch'  => 10,
            'iat' => $now,
            'exp' => $now + 180,
            'nce' => 'a7f3c1',
        ];
    }

    private function verifier(?string $secret = null): TokenVerifier
    {
        return new TokenVerifier([$secret ?? $this->secret]);
    }

    public function test_valid_token_passes(): void
    {
        $now = 1715000000;
        $token = TokenVerifier::sign($this->validPayload($now), $this->secret);
        $r = $this->verifier()->verify($token, $now);
        $this->assertTrue($r['ok']);
        $this->assertSame('10003586742996289', $r['payload']['sub']);
        $this->assertSame(10, $r['payload']['ch']);
    }

    public function test_malformed_no_dot(): void
    {
        $r = $this->verifier()->verify('no-dot-here', 1715000000);
        $this->assertFalse($r['ok']);
        $this->assertSame(TokenVerifier::ERR_MALFORMED, $r['reason']);
    }

    public function test_malformed_bad_base64(): void
    {
        $r = $this->verifier()->verify('!!!.!!!', 1715000000);
        $this->assertFalse($r['ok']);
        $this->assertSame(TokenVerifier::ERR_MALFORMED, $r['reason']);
    }

    public function test_bad_signature(): void
    {
        $now = 1715000000;
        $token = TokenVerifier::sign($this->validPayload($now), 'wrong-secret');
        $r = $this->verifier()->verify($token, $now);
        $this->assertFalse($r['ok']);
        $this->assertSame(TokenVerifier::ERR_BAD_SIG, $r['reason']);
    }

    public function test_expired(): void
    {
        $now = 1715000000;
        $token = TokenVerifier::sign($this->validPayload($now), $this->secret);
        // 200 seconds later — past exp (now+180)
        $r = $this->verifier()->verify($token, $now + 200);
        $this->assertFalse($r['ok']);
        $this->assertSame(TokenVerifier::ERR_EXPIRED, $r['reason']);
    }

    public function test_future_iat_beyond_skew(): void
    {
        $now = 1715000000;
        // iat is 60s in the future but we evaluate at $now → outside SKEW (30)
        $payload = $this->validPayload($now + 60);
        $token = TokenVerifier::sign($payload, $this->secret);
        $r = $this->verifier()->verify($token, $now);
        $this->assertFalse($r['ok']);
        $this->assertSame(TokenVerifier::ERR_FUTURE_IAT, $r['reason']);
    }

    public function test_future_iat_within_skew_passes(): void
    {
        $now = 1715000000;
        $payload = $this->validPayload($now + 10);  // within 30s skew
        $token = TokenVerifier::sign($payload, $this->secret);
        $r = $this->verifier()->verify($token, $now);
        $this->assertTrue($r['ok']);
    }

    public function test_unknown_channel_rejected(): void
    {
        $now = 1715000000;
        $p = $this->validPayload($now);
        $p['ch'] = 11;  // not in allowed list
        $token = TokenVerifier::sign($p, $this->secret);
        $r = $this->verifier()->verify($token, $now);
        $this->assertFalse($r['ok']);
        $this->assertSame(TokenVerifier::ERR_BAD_CHANNEL, $r['reason']);
    }

    public function test_missing_field_rejected(): void
    {
        $now = 1715000000;
        $p = $this->validPayload($now);
        unset($p['nce']);
        $token = TokenVerifier::sign($p, $this->secret);
        $r = $this->verifier()->verify($token, $now);
        $this->assertFalse($r['ok']);
        $this->assertSame(TokenVerifier::ERR_BAD_PAYLOAD, $r['reason']);
    }

    public function test_wrong_type_rejected(): void
    {
        $now = 1715000000;
        $p = $this->validPayload($now);
        $p['ch'] = '10';  // string instead of int
        $token = TokenVerifier::sign($p, $this->secret);
        $r = $this->verifier()->verify($token, $now);
        $this->assertFalse($r['ok']);
        $this->assertSame(TokenVerifier::ERR_BAD_PAYLOAD, $r['reason']);
    }

    public function test_invalid_nonce_format(): void
    {
        $now = 1715000000;
        $p = $this->validPayload($now);
        $p['nce'] = 'XYZ123';  // uppercase + non-hex
        $token = TokenVerifier::sign($p, $this->secret);
        $r = $this->verifier()->verify($token, $now);
        $this->assertFalse($r['ok']);
        $this->assertSame(TokenVerifier::ERR_BAD_PAYLOAD, $r['reason']);
    }

    public function test_previous_secret_accepted_during_rotation(): void
    {
        $now = 1715000000;
        $token = TokenVerifier::sign($this->validPayload($now), 'old-secret');
        // verifier configured with new + old; new is tried first then old
        $verifier = new TokenVerifier(['new-secret', 'old-secret']);
        $r = $verifier->verify($token, $now);
        $this->assertTrue($r['ok']);
    }

    public function test_empty_secrets_in_list_skipped(): void
    {
        $now = 1715000000;
        $token = TokenVerifier::sign($this->validPayload($now), $this->secret);
        // Empty PREVIOUS env shouldn't accept anything by itself — must
        // still match the real secret in the list
        $verifier = new TokenVerifier([$this->secret, '']);
        $this->assertTrue($verifier->verify($token, $now)['ok']);
        // A token signed against empty string must not pass
        $bad = TokenVerifier::sign($this->validPayload($now), '');
        $this->assertFalse($verifier->verify($bad, $now)['ok']);
    }

    public function test_b64url_round_trip(): void
    {
        foreach (['', 'a', 'ab', 'abc', 'abcd', 'binary' . chr(0xff) . chr(0xfe)] as $raw) {
            $encoded = TokenVerifier::b64urlEncode($raw);
            $this->assertSame($raw, TokenVerifier::b64urlDecode($encoded));
            $this->assertStringNotContainsString('=', $encoded);
            $this->assertStringNotContainsString('+', $encoded);
            $this->assertStringNotContainsString('/', $encoded);
        }
    }
}
