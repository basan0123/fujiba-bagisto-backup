<?php

namespace Fujiba\MessengerBridge\Console\Commands;

use Fujiba\MessengerBridge\Services\ChannelMapper;
use Fujiba\MessengerBridge\Services\TokenVerifier;
use Illuminate\Console\Command;

/**
 * End-to-end test helper.
 *
 *   php artisan messenger-bridge:sign sub=10003586742996289 ch=10
 *   php artisan messenger-bridge:sign sub=... ch=9 r=/products/foo
 *
 * Prints a ready-to-paste URL pointing at the right channel host. Uses
 * the active secret (MESSENGER_BRIDGE_SECRET). Refuses to run without one.
 *
 * NOT for production use. The real signer lives on the ChatPion side.
 */
class SignBridgeTokenCommand extends Command
{
    protected $signature   = 'messenger-bridge:sign
                              {sub : ChatPion subscriber id}
                              {ch  : ChatPion store id (9=mmfood, 10=kimono)}
                              {--r= : Optional relative redirect path (must start with /)}
                              {--ttl=180 : Token TTL seconds}';

    protected $description = 'Sign a messenger-bridge token for end-to-end testing.';

    public function handle(): int
    {
        $secret = (string) env('MESSENGER_BRIDGE_SECRET', '');
        if ($secret === '') {
            $this->error('MESSENGER_BRIDGE_SECRET is not set in .env');
            return self::FAILURE;
        }

        $sub = (string) $this->argument('sub');
        $ch  = (int)    $this->argument('ch');
        $ttl = max(10, (int) $this->option('ttl'));
        $r   = (string) ($this->option('r') ?? '');

        if (! ChannelMapper::isAllowed($ch)) {
            $this->error("Unknown ch={$ch}. Allowed: 9 (mmfood), 10 (kimono).");
            return self::FAILURE;
        }

        $now = time();
        $payload = [
            'sub' => $sub,
            'ch'  => $ch,
            'iat' => $now,
            'exp' => $now + $ttl,
            'nce' => bin2hex(random_bytes(3)),
        ];

        $token = TokenVerifier::sign($payload, $secret);
        $host  = ChannelMapper::expectedHost($ch);

        $url = "https://{$host}/messenger-bridge?t=" . urlencode($token);
        if ($r !== '') {
            $url .= '&r=' . urlencode($r);
        }

        $this->line('');
        $this->line("Channel : " . ChannelMapper::label($ch) . " (ch={$ch}, bagisto_channel_id=" . ChannelMapper::bagistoChannelId($ch) . ")");
        $this->line("Host    : {$host}");
        $this->line("Sub     : {$sub}");
        $this->line("TTL     : {$ttl}s (expires " . date('Y-m-d H:i:s', $now + $ttl) . ')');
        $this->line("Nonce   : {$payload['nce']}");
        $this->line('');
        $this->line('URL:');
        $this->line($url);
        $this->line('');

        return self::SUCCESS;
    }
}
