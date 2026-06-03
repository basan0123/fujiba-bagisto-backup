<?php

namespace Fujiba\CustomerRanks\Jobs;

use Fujiba\CustomerRanks\Services\PromoteCustomerRankService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Re-evaluates one customer's loyalty rank after an order reaches a
 * qualifying status. Tenant context is restored automatically by
 * Stancl's QueueTenancyBootstrapper (config/tenancy.php), so this job
 * runs against the correct tenant DB.
 *
 * Dispatched afterCommit (see CustomerRanksServiceProvider) so the
 * completed order is already committed when the count query runs —
 * mirrors the notification-race fix.
 */
class EvaluateCustomerRankJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public readonly int $customerId) {}

    public function handle(PromoteCustomerRankService $service): void
    {
        $service->evaluateCustomer($this->customerId);
    }
}
