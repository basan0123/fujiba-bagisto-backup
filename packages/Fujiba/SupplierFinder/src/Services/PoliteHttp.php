<?php

namespace Fujiba\SupplierFinder\Services;

use Fujiba\SupplierFinder\Support\ShopBlocked;
use Illuminate\Support\Facades\Http;

/**
 * The only component that talks to shops. Per domain: robots.txt honoured
 * (User-agent * and our own token), at most one request per min_interval
 * seconds, one back-off on 429 then the shop is reported as blocked.
 */
class PoliteHttp
{
    /** @var array<string, float> */
    private array $lastAt = [];

    /** @var array<string, array<int, string>> host => disallowed path prefixes */
    private array $robots = [];

    private int $count = 0;

    public function __construct(private array $config) {}

    public function get(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        $this->assertAllowed($url, $host);

        for ($attempt = 1; ; $attempt++) {
            $this->throttle($host);

            try {
                $res = Http::withHeaders([
                    'User-Agent'      => $this->config['user_agent'],
                    'Accept-Language' => 'ja,en;q=0.8',
                ])->timeout((int) $this->config['timeout'])->get($url);
            } catch (\Throwable $e) {
                if ($attempt >= 3) {
                    throw new \RuntimeException("GET {$url} failed: ".$e->getMessage(), 0, $e);
                }
                sleep(5 * $attempt);

                continue;
            }

            if ($res->status() === 429 || $res->status() === 403) {
                if ($attempt >= 2) {
                    throw new ShopBlocked('blocked:'.$res->status(), "{$host} answered {$res->status()} twice for {$url}");
                }
                sleep(max((int) $res->header('Retry-After'), (int) $this->config['backoff_429']));

                continue;
            }

            if ($res->serverError() && $attempt < 3) {
                sleep(5 * $attempt);

                continue;
            }

            if (! $res->successful()) {
                throw new \RuntimeException("GET {$url} → HTTP {$res->status()}");
            }

            return $res->body();
        }
    }

    public function requestCount(): int
    {
        return $this->count;
    }

    private function throttle(string $host): void
    {
        $wait = (($this->lastAt[$host] ?? 0) + (float) $this->config['min_interval']) - microtime(true);
        if ($wait > 0) {
            usleep((int) ($wait * 1_000_000));
        }
        $this->lastAt[$host] = microtime(true);
        $this->count++;
    }

    private function assertAllowed(string $url, string $host): void
    {
        if (parse_url($url, PHP_URL_SCHEME) !== 'https') {
            throw new \RuntimeException("Refusing non-https URL {$url}");
        }

        if (! isset($this->robots[$host])) {
            $this->robots[$host] = [];
            $this->throttle($host);
            try {
                $res = Http::withHeaders(['User-Agent' => $this->config['user_agent']])
                    ->timeout((int) $this->config['timeout'])->get("https://{$host}/robots.txt");
                if ($res->status() === 401 || $res->status() === 403) {
                    throw new ShopBlocked('blocked:robots', "{$host} robots.txt answered {$res->status()} — treated as full disallow");
                }
                if ($res->successful()) {
                    $this->robots[$host] = self::parseRobots($res->body(), 'FujibaSupplierFinder');
                }
            } catch (ShopBlocked $e) {
                throw $e;
            } catch (\Throwable) {
                // unreachable robots.txt = no restrictions (RFC 9309 §2.3.1.4 for 4xx; network errors are retried by the page fetch)
            }
        }

        $path = (parse_url($url, PHP_URL_PATH) ?: '/').(($q = parse_url($url, PHP_URL_QUERY)) ? "?{$q}" : '');
        foreach ($this->robots[$host] as $rule) {
            if ($rule !== '' && self::robotsMatch($rule, $path)) {
                throw new ShopBlocked('blocked:robots', "robots.txt disallows {$url}");
            }
        }
    }

    /** Disallow lines for our token if a group names it, else for "*". Allow lines are ignored (conservative). */
    public static function parseRobots(string $txt, string $token): array
    {
        $groups = [];
        $agents = [];
        $inRules = false;
        foreach (preg_split('/\R/', $txt) as $line) {
            $line = trim(preg_replace('/#.*/', '', $line));
            if (! str_contains($line, ':')) {
                continue;
            }
            [$k, $v] = array_map('trim', explode(':', $line, 2));
            $k = strtolower($k);
            if ($k === 'user-agent') {
                if ($inRules) {
                    $agents = [];
                    $inRules = false;
                }
                $agents[] = strtolower($v);
                foreach ($agents as $a) {
                    $groups[$a] ??= [];
                }
            } elseif ($k === 'disallow' || $k === 'allow') {
                $inRules = true;
                if ($k === 'disallow') {
                    foreach ($agents as $a) {
                        $groups[$a][] = $v;
                    }
                }
            }
        }

        return $groups[strtolower($token)] ?? $groups['*'] ?? [];
    }

    private static function robotsMatch(string $rule, string $path): bool
    {
        $re = '#^'.str_replace(['\*', '\$'], ['.*', '$'], preg_quote($rule, '#')).'#';

        return (bool) preg_match($re, $path);
    }
}
