<?php

namespace Fujiba\AutoPoster\Tests\Unit\Ops;

use Fujiba\AutoPoster\Ops\Guard\LoadGuard;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use PHPUnit\Framework\TestCase;

class LoadGuardTest extends TestCase
{
    private function guard(?float $load, ?float $ms, ?Repository $cache = null): array
    {
        $cache ??= new Repository(new ArrayStore());
        $calls = 0;
        $g = new LoadGuard($cache, ['probe_ttl' => 0], fn () => $load, function () use ($ms, &$calls) { $calls++; return $ms; });

        return [$g, $cache, fn () => $calls];
    }

    public function test_denies_when_load_above_threshold(): void
    {
        [$g] = $this->guard(4.5, 100);
        $d = $g->check();
        $this->assertFalse($d->allowed);
        $this->assertSame('load', $d->reason);
    }

    public function test_allows_at_threshold_and_seeds_baseline(): void
    {
        [$g, $c] = $this->guard(4.2, 300);
        $this->assertTrue($g->check()->allowed);
        $this->assertEquals(300, $c->get(LoadGuard::BASELINE_KEY));
    }

    public function test_denies_when_site_response_doubles_baseline(): void
    {
        $c = new Repository(new ArrayStore());
        $c->forever(LoadGuard::BASELINE_KEY, 300.0);
        [$g] = $this->guard(1.0, 600, $c);
        $d = $g->check();
        $this->assertFalse($d->allowed);
        $this->assertSame('site_slow', $d->reason);
        $this->assertEquals(300, $c->get(LoadGuard::BASELINE_KEY), 'slow probe must not move baseline');
    }

    public function test_baseline_is_ema_of_healthy_probes(): void
    {
        $c = new Repository(new ArrayStore());
        $c->forever(LoadGuard::BASELINE_KEY, 300.0);
        [$g] = $this->guard(1.0, 400, $c);
        $this->assertTrue($g->check()->allowed);
        $this->assertEquals(310, $c->get(LoadGuard::BASELINE_KEY));
    }

    public function test_denies_when_site_unreachable(): void
    {
        [$g] = $this->guard(0.5, null);
        $this->assertSame('site_unreachable', $g->check()->reason);
    }

    public function test_probe_result_is_cached(): void
    {
        $c = new Repository(new ArrayStore());
        $calls = 0;
        $g = new LoadGuard($c, ['probe_ttl' => 60], fn () => 1.0, function () use (&$calls) { $calls++; return 200.0; });
        $g->check(); $g->check(); $g->check();
        $this->assertSame(1, $calls);
    }
}
