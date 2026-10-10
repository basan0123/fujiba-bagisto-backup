<?php

namespace Fujiba\AutoPoster\Ops\Jobs\Middleware;

use Fujiba\AutoPoster\Ops\Guard\LoadGuard;
use Illuminate\Support\Facades\Log;

/**
 * Job middleware: if the guard trips, the job is released back onto its queue
 * (not failed, does not burn a retry meaningfully) with a delay.
 *
 * Releases do count as attempts in Laravel, so ReliableIntelJob sets a
 * time-based retryUntil() rather than a small $tries.
 */
class LoadGuardMiddleware
{
    public function __construct(private ?LoadGuard $guard = null) {}

    public function handle(object $job, callable $next): mixed
    {
        $guard = $this->guard ?? app(LoadGuard::class);
        $decision = $guard->check();

        if (! $decision->allowed) {
            Log::channel(config('fujiba_intel.log_channel', 'stack'))->info('[fujiba-intel] load guard paused job', [
                'job' => $job::class, 'reason' => $decision->reason, 'detail' => $decision->detail,
            ]);
            if (method_exists($job, 'release')) {
                $job->release($guard->releaseDelay());
            }

            return null;
        }

        return $next($job);
    }
}
