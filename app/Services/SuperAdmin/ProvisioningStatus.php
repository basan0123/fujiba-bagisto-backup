<?php
namespace App\Services\SuperAdmin;

use App\Models\TenantProvisioningJob;

class ProvisioningStatus
{
    public function __construct(private TenantProvisioningJob $job) {}

    public static function for(int $jobId): self
    {
        return new self(TenantProvisioningJob::findOrFail($jobId));
    }

    public function start(string $stepKey, ?string $message = null): void
    {
        $this->mutate(function (array $steps) use ($stepKey, $message) {
            $steps[$stepKey]['status']     = 'running';
            $steps[$stepKey]['message']    = $message;
            $steps[$stepKey]['started_at'] = now()->toDateTimeString();
            return $steps;
        }, overall: 'running', message: $message);
    }

    public function done(string $stepKey, ?string $message = null): void
    {
        $this->mutate(function (array $steps) use ($stepKey, $message) {
            $steps[$stepKey]['status']      = 'done';
            if ($message !== null) {
                $steps[$stepKey]['message'] = $message;
            }
            $steps[$stepKey]['finished_at'] = now()->toDateTimeString();
            return $steps;
        }, message: $message);
    }

    public function fail(string $stepKey, string $message): void
    {
        $this->mutate(function (array $steps) use ($stepKey, $message) {
            $steps[$stepKey]['status']      = 'failed';
            $steps[$stepKey]['message']     = $message;
            $steps[$stepKey]['finished_at'] = now()->toDateTimeString();
            return $steps;
        }, overall: 'failed', message: $message);
        $this->job->update(['finished_at' => now()]);
    }

    public function finish(string $loginUrl): void
    {
        $this->job->refresh();
        $this->job->update([
            'overall_status' => 'done',
            'last_message'   => 'Done. Login: ' . $loginUrl,
            'finished_at'    => now(),
        ]);
    }

    public function tenantId(string $id): void
    {
        $this->job->update(['tenant_id' => $id]);
    }

    public function began(): void
    {
        if (! $this->job->started_at) {
            $this->job->update(['started_at' => now()]);
        }
    }

    public function job(): TenantProvisioningJob
    {
        return $this->job->refresh();
    }

    private function mutate(\Closure $fn, ?string $overall = null, ?string $message = null): void
    {
        $this->job->refresh();
        $steps = $this->job->steps ?? TenantProvisioningJob::freshStepsArray();
        $steps = $fn($steps);
        $patch = ['steps' => $steps];
        if ($overall) {
            $patch['overall_status'] = $overall;
        }
        if ($message !== null) {
            $patch['last_message'] = $message;
        }
        $this->job->update($patch);
    }
}
