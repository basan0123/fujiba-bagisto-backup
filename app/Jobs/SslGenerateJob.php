<?php
namespace App\Jobs;

use App\Models\Tenant;
use App\Services\SuperAdmin\SslService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SslGenerateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 1;

    public function __construct(
        public string $tenantId,
        public string $domain,
        public string $email,
        public string $action = 'generate',
    ) {}

    public function handle(SslService $ssl): void
    {
        $tenant = Tenant::findOrFail($this->tenantId);

        match ($this->action) {
            'renew'    => $ssl->renew($tenant, $this->domain),
            default    => $ssl->generate($tenant, $this->domain, $this->email),
        };
    }
}
