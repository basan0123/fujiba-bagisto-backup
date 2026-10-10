<?php

namespace Fujiba\AutoPoster\Ops\Jobs\Concerns;

use Fujiba\AutoPoster\Ops\Jobs\Middleware\LoadGuardMiddleware;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reliability contract for intelligence-layer jobs.
 *
 * Use on a class that implements ShouldQueue (+ ShouldBeUnique for dispatch
 * de-duplication) and uses Dispatchable/InteractsWithQueue/Queueable.
 * Implement:
 *   - idempotencyKey(): string   stable key for "this unit of work"
 *   - process(): void            the work; call checkpoint() between stages
 *
 * Gives you:
 *   - idempotency: a completed key is remembered (done_ttl) and re-runs no-op
 *   - checkpointing: resumable stage/state persisted across retries/releases
 *   - retries with exponential backoff, bounded by time (retryUntil) and by
 *     real exceptions (maxExceptions) so load-guard releases don't exhaust it
 *   - load guard middleware + per-key WithoutOverlapping
 */
trait ReliableIntelJob
{
    /** Real exceptions allowed before the job is failed (releases don't count). */
    public int $maxExceptions = 3;

    public int $timeout = 900;

    public bool $failOnTimeout = true;

    /** Set false inside process() for outcomes that must re-run later (e.g. blocked). */
    protected bool $markDoneOnSuccess = true;

    abstract public function idempotencyKey(): string;

    abstract protected function process(): void;

    public function handle(): void
    {
        if ($this->alreadyDone()) {
            Log::info('[fujiba-intel] skip, already done', ['job' => static::class, 'key' => $this->idempotencyKey()]);

            return;
        }

        $this->process();

        if ($this->markDoneOnSuccess) {
            $this->markDone();
        }
    }

    /** Laravel ShouldBeUnique hook: prevents duplicate pending dispatches. */
    public function uniqueId(): string
    {
        return $this->idempotencyKey();
    }

    public function uniqueFor(): int
    {
        return 6 * 3600;
    }

    public function backoff(): array
    {
        return [60, 300, 900, 1800];
    }

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addHours(12);
    }

    public function middleware(): array
    {
        return [
            new LoadGuardMiddleware(),
            (new WithoutOverlapping('fi:'.$this->idempotencyKey()))->releaseAfter(120)->expireAfter($this->timeout + 60),
        ];
    }

    public function failed(?Throwable $e): void
    {
        Log::warning('[fujiba-intel] job failed permanently', [
            'job' => static::class, 'key' => $this->idempotencyKey(),
            'stage' => $this->checkpointStage(), 'error' => $e?->getMessage(),
        ]);
    }

    // ---- checkpointing -------------------------------------------------

    protected function checkpoint(string $stage, array $state = []): void
    {
        $this->opsCache()->put($this->checkpointKey(), ['stage' => $stage, 'state' => $state, 'at' => now()->toIso8601String()], 7 * 86400);
    }

    protected function checkpointStage(): ?string
    {
        return $this->opsCache()->get($this->checkpointKey())['stage'] ?? null;
    }

    protected function checkpointState(): array
    {
        return $this->opsCache()->get($this->checkpointKey())['state'] ?? [];
    }

    /** True if $stage was reached in an earlier attempt (given ordered stages). */
    protected function passed(string $stage, array $orderedStages): bool
    {
        $current = $this->checkpointStage();
        if ($current === null) {
            return false;
        }
        $ci = array_search($current, $orderedStages, true);
        $si = array_search($stage, $orderedStages, true);

        return $ci !== false && $si !== false && $ci >= $si;
    }

    // ---- idempotency ---------------------------------------------------

    public function alreadyDone(): bool
    {
        return (bool) $this->opsCache()->get($this->doneKey());
    }

    protected function markDone(): void
    {
        $this->opsCache()->put($this->doneKey(), now()->toIso8601String(), (int) config('fujiba_intel.done_ttl', 30 * 86400));
        $this->opsCache()->forget($this->checkpointKey());
    }

    private function checkpointKey(): string
    {
        return 'fujiba_intel:ckpt:'.sha1(static::class.'|'.$this->idempotencyKey());
    }

    private function doneKey(): string
    {
        return 'fujiba_intel:done:'.sha1(static::class.'|'.$this->idempotencyKey());
    }

    private function opsCache(): \Illuminate\Contracts\Cache\Repository
    {
        return Cache::store(config('fujiba_intel.cache_store'));
    }
}
