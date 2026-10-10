<?php

namespace Fujiba\AutoPoster\Tests\Unit\Ops;

use Fujiba\AutoPoster\Ops\Insights\GraphClient;
use Fujiba\AutoPoster\Ops\Insights\GraphException;
use Fujiba\AutoPoster\Ops\Insights\MetaInsightsImporter;
use Fujiba\AutoPoster\Ops\Insights\MetaInsightsTokenProvider;
use Fujiba\AutoPoster\Ops\Insights\PerformanceStore;
use Fujiba\AutoPoster\Ops\Insights\PublishedPostSource;
use PHPUnit\Framework\TestCase;

/** Recorded-response tests only. No network, no DB. */
class MetaInsightsImporterTest extends TestCase
{
    public array $calls = [];

    private function fx(string $n): array
    {
        return json_decode(file_get_contents(__DIR__."/fixtures/$n.json"), true);
    }

    private function make(?string $token, array $posts, ?\Closure $responder = null, ?PerformanceStore &$store = null): MetaInsightsImporter
    {
        $tokens = new class($token) implements MetaInsightsTokenProvider {
            public function __construct(private ?string $t) {}
            public function token(): ?string { return $this->t; }
        };
        $test = $this;
        $responder ??= function (string $path) {
            if (str_starts_with($path, 'IG')) return $this->fx('ig_insights');
            return str_ends_with($path, '/insights') ? $this->fx('fb_insights') : $this->fx('fb_object');
        };
        $graph = new class($responder, $test) implements GraphClient {
            public function __construct(private \Closure $r, private $test) {}
            public function get(string $path, array $query, string $token): array
            {
                $this->test->calls[] = [$path, $query];
                return ($this->r)($path, $query);
            }
        };
        $source = new class($posts) extends PublishedPostSource {
            public function __construct(private array $p) {}
            public function posts(): array { return $this->p; }
        };
        $store = new class extends PerformanceStore {
            public array $rows = [];
            public function upsert(int $c, string $d, string $s, array $m): void { $this->rows["$c|$d|$s"] = $m; }
        };

        return new MetaInsightsImporter($tokens, $graph, $source, $store);
    }

    private function post(string $platform, string $id, ?int $creative): array
    {
        return ['queue_id' => 1, 'platform' => $platform, 'external_id' => $id, 'published_at' => '2026-10-09', 'creative_id' => $creative];
    }

    public function test_no_token_is_blocked_and_makes_no_calls(): void
    {
        $r = $this->make(null, [$this->post('fb', 'FB_1', 5)])->run('2026-10-10');
        $this->assertSame('blocked:no_token', $r['status']);
        $this->assertSame([], $this->calls);
    }

    public function test_maps_fb_and_ig_metrics_into_separate_sources(): void
    {
        $imp = $this->make('t', [$this->post('fb', 'FB_1', 5), $this->post('ig', 'IG_1', 5), $this->post('fb', 'FB_2', null)], null, $store);
        $r = $imp->run('2026-10-10');

        $this->assertSame('ok', $r['status']);
        $this->assertSame(2, $r['written']);
        $this->assertSame(1, $r['unmapped']);
        $this->assertSame(['reach' => 812, 'impressions' => 1530, 'video_views' => 0, 'clicks' => 47, 'engagement' => 27], $store->rows['5|2026-10-10|meta_api:fb']);
        $this->assertSame(['reach' => 640, 'impressions' => 990, 'video_views' => 990, 'clicks' => 0, 'engagement' => 55], $store->rows['5|2026-10-10|meta_api:ig']);
    }

    public function test_only_reads_are_issued(): void
    {
        $this->make('t', [$this->post('fb', 'FB_1', 5)])->run();
        $this->assertNotEmpty($this->calls);
        $this->assertFalse(method_exists(GraphClient::class, 'post'));
    }

    public function test_auth_error_stops_run_as_blocked(): void
    {
        $r = $this->make('t', [$this->post('fb', 'FB_1', 5), $this->post('fb', 'FB_2', 6)], fn () => throw new GraphException('expired', 190, 400))->run();
        $this->assertSame('blocked:auth', $r['status']);
        $this->assertCount(1, $this->calls);
    }

    public function test_per_post_error_is_partial(): void
    {
        $r = $this->make('t', [$this->post('fb', 'FB_1', 5), $this->post('ig', 'IG_1', 6)], function ($path) {
            if (str_starts_with($path, 'FB')) throw new GraphException('Unsupported get request', 100, 400);
            return $this->fx('ig_insights');
        })->run();
        $this->assertSame('partial', $r['status']);
        $this->assertSame(1, $r['written']);
        $this->assertSame(1, $r['failed']);
    }

    public function test_rate_limit_is_retryable_error(): void
    {
        $r = $this->make('t', [$this->post('ig', 'IG_1', 6)], fn () => throw new GraphException('limit', 4, 400))->run();
        $this->assertSame('error:rate_limited', $r['status']);
    }
}
