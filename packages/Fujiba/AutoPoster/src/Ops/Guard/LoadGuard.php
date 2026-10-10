<?php

namespace Fujiba\AutoPoster\Ops\Guard;

use Closure;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Decides whether background intelligence work may run right now.
 *
 * Trips when EITHER:
 *  - 1-minute load average > max_load (default 4.2 on the 6-core VPS), or
 *  - the storefront (kimono.fujiba.co.jp) responds >= slowdown_factor x the
 *    stored baseline (default 2.0 = "response time doubled").
 *
 * The baseline is a slow EMA kept in cache, updated only from healthy probes,
 * so a slow period cannot drag the baseline up and hide itself. The site
 * probe result is cached for probe_ttl seconds so many jobs do not hammer the
 * storefront. Probe = one GET of the home page (read only).
 */
class LoadGuard
{
    public const BASELINE_KEY = 'fujiba_intel:site_baseline_ms';
    public const PROBE_KEY = 'fujiba_intel:site_probe_ms';

    /** @var Closure():?float */
    private Closure $loadReader;

    /** @var Closure():?float */
    private Closure $siteProbe;

    public function __construct(
        private Cache $cache,
        private array $config = [],
        ?Closure $loadReader = null,
        ?Closure $siteProbe = null,
    ) {
        $this->config += [
            'max_load'        => 4.2,
            'slowdown_factor' => 2.0,
            'site_url'        => 'https://kimono.fujiba.co.jp/',
            'probe_ttl'       => 60,
            'probe_timeout'   => 10,
            'baseline_alpha'  => 0.1,
            'release_delay'   => 300,
        ];
        $this->loadReader = $loadReader ?? static function (): ?float {
            $l = function_exists('sys_getloadavg') ? sys_getloadavg() : false;

            return $l === false ? null : (float) $l[0];
        };
        $this->siteProbe = $siteProbe ?? function (): ?float {
            try {
                $t = microtime(true);
                $r = Http::timeout($this->config['probe_timeout'])
                    ->withHeaders(['User-Agent' => 'FujibaIntelLoadGuard/1.0 (+support-f@fujiba.shop)'])
                    ->get($this->config['site_url']);
                $ms = (microtime(true) - $t) * 1000;

                return $r->successful() ? $ms : null;
            } catch (Throwable) {
                return null;
            }
        };
    }

    public function check(): GuardDecision
    {
        $load = ($this->loadReader)();
        if ($load !== null && $load > $this->config['max_load']) {
            return GuardDecision::deny('load', sprintf('load %.2f > %.2f', $load, $this->config['max_load']), $load);
        }

        $ms = $this->probeSite();
        $baseline = $this->baseline();

        if ($ms === null) {
            // Site down/unreachable: back off - do not add load to a struggling box.
            return GuardDecision::deny('site_unreachable', 'storefront probe failed', $load, null, $baseline);
        }

        if ($baseline !== null && $ms >= $baseline * $this->config['slowdown_factor']) {
            return GuardDecision::deny('site_slow', sprintf('site %.0fms >= %.1fx baseline %.0fms', $ms, $this->config['slowdown_factor'], $baseline), $load, $ms, $baseline);
        }

        $this->updateBaseline($ms, $baseline);

        return GuardDecision::allow($load, $ms, $baseline);
    }

    public function releaseDelay(): int
    {
        return (int) $this->config['release_delay'];
    }

    public function baseline(): ?float
    {
        $b = $this->cache->get(self::BASELINE_KEY);

        return $b === null ? null : (float) $b;
    }

    private function probeSite(): ?float
    {
        $cached = $this->cache->get(self::PROBE_KEY);
        if ($cached !== null) {
            return $cached === 'fail' ? null : (float) $cached;
        }
        $ms = ($this->siteProbe)();
        $this->cache->put(self::PROBE_KEY, $ms ?? 'fail', $this->config['probe_ttl']);

        return $ms;
    }

    private function updateBaseline(float $ms, ?float $baseline): void
    {
        $a = $this->config['baseline_alpha'];
        $new = $baseline === null ? $ms : ($a * $ms + (1 - $a) * $baseline);
        $this->cache->forever(self::BASELINE_KEY, round($new, 1));
    }
}
