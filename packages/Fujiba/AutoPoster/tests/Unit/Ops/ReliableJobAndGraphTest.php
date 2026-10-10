<?php

namespace Fujiba\AutoPoster\Tests\Unit\Ops;

use Fujiba\AutoPoster\Ops\Guard\GuardDecision;
use Fujiba\AutoPoster\Ops\Guard\LoadGuard;
use Fujiba\AutoPoster\Ops\Insights\ConfigMetaInsightsTokenProvider;
use Fujiba\AutoPoster\Ops\Insights\GraphException;
use Fujiba\AutoPoster\Ops\Insights\HttpGraphClient;
use Fujiba\AutoPoster\Ops\IntelOpsServiceProvider;
use Fujiba\AutoPoster\Ops\IntelQueues;
use Fujiba\AutoPoster\Ops\Jobs\Concerns\ReliableIntelJob;
use Fujiba\AutoPoster\Ops\Jobs\ImportMetaInsightsJob;
use Fujiba\AutoPoster\Ops\Jobs\Middleware\LoadGuardMiddleware;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Client\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\TestCase;

class FakeStagedJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, ReliableIntelJob;

    public static array $ran = [];
    public static bool $failAtB = false;

    public function idempotencyKey(): string { return 'fake:1'; }

    protected function process(): void
    {
        $stages = ['a', 'b'];
        if (! $this->passed('a', $stages)) { self::$ran[] = 'a'; $this->checkpoint('a', ['n' => 1]); }
        if (self::$failAtB) { throw new \RuntimeException('boom'); }
        self::$ran[] = 'b:'.($this->checkpointState()['n'] ?? 0);
        $this->checkpoint('b');
    }
}

class ReliableJobAndGraphTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'fujiba_intel.cache_store' => 'array']);
        $this->app->register(IntelOpsServiceProvider::class);
        FakeStagedJob::$ran = [];
        FakeStagedJob::$failAtB = false;
        Http::preventStrayRequests();
    }

    public function test_checkpoint_resumes_and_idempotency_skips_rerun(): void
    {
        FakeStagedJob::$failAtB = true;
        try { (new FakeStagedJob())->handle(); } catch (\RuntimeException) {}
        $this->assertSame(['a'], FakeStagedJob::$ran);

        FakeStagedJob::$failAtB = false;
        (new FakeStagedJob())->handle();           // retry: skips stage a, sees its state
        $this->assertSame(['a', 'b:1'], FakeStagedJob::$ran);

        (new FakeStagedJob())->handle();           // duplicate delivery: no-op
        $this->assertSame(['a', 'b:1'], FakeStagedJob::$ran);
        $this->assertTrue((new FakeStagedJob())->alreadyDone());
    }

    public function test_retry_policy(): void
    {
        $j = new FakeStagedJob();
        $this->assertSame([60, 300, 900, 1800], $j->backoff());
        $this->assertSame(3, $j->maxExceptions);
        $this->assertSame('fake:1', $j->uniqueId());
        $this->assertGreaterThan(now()->addHours(11), $j->retryUntil());
        $this->assertInstanceOf(LoadGuardMiddleware::class, $j->middleware()[0]);
    }

    public function test_middleware_releases_when_guard_denies(): void
    {
        $guard = $this->createMock(LoadGuard::class);
        $guard->method('check')->willReturn(GuardDecision::deny('load', 'load 5 > 4.2', 5.0));
        $guard->method('releaseDelay')->willReturn(300);

        $job = new class { public ?int $released = null; public function release($d) { $this->released = $d; } };
        $ran = false;
        (new LoadGuardMiddleware($guard))->handle($job, function () use (&$ran) { $ran = true; });

        $this->assertFalse($ran);
        $this->assertSame(300, $job->released);
    }

    public function test_middleware_passes_when_allowed(): void
    {
        $guard = $this->createMock(LoadGuard::class);
        $guard->method('check')->willReturn(GuardDecision::allow(1.0, 200, 200));
        $ran = false;
        (new LoadGuardMiddleware($guard))->handle(new \stdClass(), function () use (&$ran) { $ran = true; });
        $this->assertTrue($ran);
    }

    public function test_insights_job_on_insights_queue(): void
    {
        $j = new ImportMetaInsightsJob('tid', '2026-10-10');
        $this->assertSame(IntelQueues::INSIGHTS, $j->queue);
        $this->assertSame('insights:tid:2026-10-10', $j->idempotencyKey());
    }

    public function test_token_provider_null_until_configured(): void
    {
        config(['fujiba_intel.meta_insights_token' => null]);
        $this->assertNull((new ConfigMetaInsightsTokenProvider())->token());
        config(['fujiba_intel.meta_insights_token' => '  ']);
        $this->assertNull((new ConfigMetaInsightsTokenProvider())->token());
        config(['fujiba_intel.meta_insights_token' => 'FAKE']);
        $this->assertSame('FAKE', (new ConfigMetaInsightsTokenProvider())->token());
    }

    public function test_insights_command_reports_blocked_without_token(): void
    {
        config(['fujiba_intel.meta_insights_token' => null]);
        $this->artisan('fujiba:intel:insights', ['--date' => '2026-10-10'])
            ->expectsOutputToContain('blocked:no_token')->assertExitCode(0);
    }

    public function test_http_graph_client_is_get_only_with_header_token(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['data' => []])]);
        (new HttpGraphClient('v25.0'))->get('123/insights', ['metric' => 'reach'], 'FAKE');
        Http::assertSent(fn (Request $r) => $r->method() === 'GET'
            && str_starts_with($r->url(), 'https://graph.facebook.com/v25.0/123/insights')
            && ! str_contains($r->url(), 'FAKE')
            && $r->hasHeader('Authorization', 'Bearer FAKE'));
    }

    public function test_http_graph_client_maps_errors(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'Error validating access token', 'code' => 190]], 400)]);
        try {
            (new HttpGraphClient())->get('1', [], 'FAKE');
            $this->fail('expected exception');
        } catch (GraphException $e) {
            $this->assertTrue($e->isAuthError());
        }
    }
}
