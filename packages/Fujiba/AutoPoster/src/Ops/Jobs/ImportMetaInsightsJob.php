<?php

namespace Fujiba\AutoPoster\Ops\Jobs;

use Fujiba\AutoPoster\Ops\Insights\MetaInsightsImporter;
use Fujiba\AutoPoster\Ops\IntelQueues;
use Fujiba\AutoPoster\Ops\Jobs\Concerns\ReliableIntelJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Daily Insights import for one tenant, on the `insights` queue. */
class ImportMetaInsightsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ReliableIntelJob;

    public function __construct(public string $tenantId, public string $date)
    {
        $this->onQueue(IntelQueues::INSIGHTS);
    }

    public function idempotencyKey(): string
    {
        return "insights:{$this->tenantId}:{$this->date}";
    }

    protected function process(): void
    {
        $run = fn () => app(MetaInsightsImporter::class)->run($this->date);

        $tenant = tenancy()->find($this->tenantId);
        $result = $tenant ? $tenant->run($run) : ['status' => 'error:no_tenant'];

        $this->checkpoint('imported', $result);

        if (str_starts_with($result['status'], 'blocked')) {
            $this->markDoneOnSuccess = false; // re-run once a token is configured
        }

        // blocked:no_token is a clean, non-retryable outcome; anything else that
        // starts with "error" should retry via the exception path.
        if (str_starts_with($result['status'], 'error')) {
            throw new \RuntimeException('insights import '.$result['status']);
        }
    }
}
