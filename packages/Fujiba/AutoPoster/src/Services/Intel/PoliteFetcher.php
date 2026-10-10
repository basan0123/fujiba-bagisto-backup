<?php

namespace Fujiba\AutoPoster\Services\Intel;

use Illuminate\Support\Facades\Http;

/**
 * The only HTTP client the research module uses.
 *
 * - robots.txt is fetched (and cached) per host and obeyed before any request.
 * - At most one request per `min_interval` seconds per host (default 5 s),
 *   persisted on disk so separate runs cannot burst either.
 * - Every response body is cached on disk; a cached copy is reused instead of
 *   re-fetching for `cache_ttl_days`.
 * - Host allow-list: social networks (Facebook/Instagram/TikTok…) are refused
 *   outright. No cookies, no login, no challenge handling — a 403/429/challenge
 *   is recorded as "blocked" and never retried.
 */
class PoliteFetcher
{
    public const USER_AGENT = 'FujibaResearchBot/1.0 (+https://fujiba.co.jp; contact support-f@fujiba.shop)';

    public const BLOCKED_HOSTS = [
        'facebook.com', 'fb.com', 'instagram.com', 'tiktok.com', 'threads.net',
        'x.com', 'twitter.com', 'messenger.com',
    ];

    private array $robots = [];

    public function __construct(
        private string $cacheDir,
        private array $allowedHosts,
        private float $minInterval = 5.0,
        private int $cacheTtlDays = 30,
    ) {
        if (! is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0775, true);
        }
    }

    /**
     * @return array{url:string,status:int|null,robots_ok:bool,cache_path:?string,body:?string,from_cache:bool,fetched_at:string,error:?string}
     */
    public function get(string $url): array
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $out = ['url' => $url, 'status' => null, 'robots_ok' => false, 'cache_path' => null,
            'body' => null, 'from_cache' => false, 'fetched_at' => now()->toDateTimeString(), 'error' => null];

        if (! $this->hostAllowed($host)) {
            $out['error'] = 'host not on research allow-list';

            return $out;
        }

        $path = $this->cachePath($url);
        $meta = $path.'.json';
        if (is_file($path) && is_file($meta) && filemtime($path) > time() - $this->cacheTtlDays * 86400) {
            $m = json_decode((string) file_get_contents($meta), true) ?: [];

            return array_merge($out, $m, ['body' => file_get_contents($path), 'cache_path' => $path, 'from_cache' => true]);
        }

        $out['robots_ok'] = $this->robotsAllows($url);
        if (! $out['robots_ok']) {
            $out['error'] = 'disallowed by robots.txt';

            return $out;
        }

        try {
            $res = $this->rawGet($url);
            $out['status'] = $res->status();
            if ($res->successful()) {
                file_put_contents($path, $res->body());
                $out['body'] = $res->body();
                $out['cache_path'] = $path;
                file_put_contents($meta, json_encode(['status' => $out['status'], 'robots_ok' => true,
                    'fetched_at' => $out['fetched_at'], 'url' => $url]));
            } else {
                $out['error'] = 'http '.$res->status();
            }
        } catch (\Throwable $e) {
            $out['error'] = mb_substr($e->getMessage(), 0, 200);
        }

        return $out;
    }

    public function hostAllowed(string $host): bool
    {
        foreach (self::BLOCKED_HOSTS as $b) {
            if ($host === $b || str_ends_with($host, '.'.$b)) {
                return false;
            }
        }

        return in_array($host, $this->allowedHosts, true);
    }

    public function robotsAllows(string $url): bool
    {
        $scheme = parse_url($url, PHP_URL_SCHEME) ?: 'https';
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $p = (string) (parse_url($url, PHP_URL_PATH) ?: '/');
        if ($q = parse_url($url, PHP_URL_QUERY)) {
            $p .= '?'.$q;
        }

        if (! isset($this->robots[$host])) {
            $rpath = $this->cachePath("$scheme://$host/robots.txt");
            if (is_file($rpath) && filemtime($rpath) > time() - 86400) {
                $txt = file_get_contents($rpath);
            } else {
                try {
                    $res = $this->rawGet("$scheme://$host/robots.txt");
                    // 4xx = no robots file = allowed; 5xx/failure = assume disallowed.
                    $txt = $res->successful() ? $res->body() : ($res->clientError() ? '' : "User-agent: *\nDisallow: /");
                } catch (\Throwable) {
                    $txt = "User-agent: *\nDisallow: /";
                }
                file_put_contents($rpath, $txt);
            }
            $this->robots[$host] = self::parseRobots((string) $txt);
        }

        return self::robotsDecide($this->robots[$host], rawurldecode($p));
    }

    /** Rules for our UA token if present, else for "*". */
    public static function parseRobots(string $txt): array
    {
        $groups = [];
        $agents = [];
        $inRules = false;
        foreach (preg_split('/\r?\n/', $txt) as $line) {
            $line = trim(preg_replace('/#.*/', '', $line));
            if ($line === '' || ! str_contains($line, ':')) {
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
            } elseif ($k === 'allow' || $k === 'disallow') {
                $inRules = true;
                foreach ($agents as $a) {
                    $groups[$a][] = [$k, $v];
                }
            }
        }

        return $groups['fujibaresearchbot'] ?? $groups['*'] ?? [];
    }

    public static function robotsDecide(array $rules, string $path): bool
    {
        $best = -1;
        $allow = true;
        foreach ($rules as [$kind, $pattern]) {
            if ($pattern === '') {
                continue;
            }
            $re = '#^'.str_replace(['\*', '\$'], ['.*', '$'], preg_quote(rawurldecode($pattern), '#')).'#u';
            if (@preg_match($re, $path) && strlen($pattern) > $best) {
                $best = strlen($pattern);
                $allow = $kind === 'allow';
            } elseif (strlen($pattern) === $best && $kind === 'allow' && @preg_match($re, $path)) {
                $allow = true;
            }
        }

        return $allow;
    }

    private function rawGet(string $url)
    {
        $this->throttle(strtolower((string) parse_url($url, PHP_URL_HOST)));

        return Http::withHeaders(['User-Agent' => self::USER_AGENT, 'Accept-Language' => 'ja,en;q=0.8'])
            ->withoutRedirecting()->timeout(20)->get($url);
    }

    private function throttle(string $host): void
    {
        $f = $this->cacheDir.'/.last-'.preg_replace('/[^a-z0-9.-]/', '_', $host);
        $fh = fopen($f, 'c+');
        flock($fh, LOCK_EX);
        $last = (float) stream_get_contents($fh);
        $wait = $last + $this->minInterval - microtime(true);
        if ($wait > 0) {
            usleep((int) ($wait * 1e6));
        }
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, (string) microtime(true));
        flock($fh, LOCK_UN);
        fclose($fh);
    }

    private function cachePath(string $url): string
    {
        $host = preg_replace('/[^a-z0-9.-]/', '_', strtolower((string) parse_url($url, PHP_URL_HOST)));
        $dir = $this->cacheDir.'/'.$host;
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir.'/'.sha1($url).'.body';
    }
}
