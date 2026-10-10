<?php

namespace Fujiba\AutoPoster\Ops\Console;

use Fujiba\AutoPoster\Ops\Insights\MetaInsightsImporter;
use Fujiba\AutoPoster\Ops\Jobs\ImportMetaInsightsJob;
use Illuminate\Console\Command;

/** Run inside a tenant (tenants:run) or with --queue to dispatch the job. GET-only. */
class ImportInsightsCommand extends Command
{
    protected $signature = 'fujiba:intel:insights {--date=} {--queue : dispatch to the insights queue instead of running inline}';

    protected $description = 'Import Meta Insights (Graph GET only) for published posts into fi_performance';

    public function handle(MetaInsightsImporter $importer): int
    {
        $date = $this->option('date') ?: now()->toDateString();

        if ($this->option('queue')) {
            ImportMetaInsightsJob::dispatch(tenant('id') ?? config('fujiba_intel.tenant_id'), $date);
            $this->line('dispatched');

            return 0;
        }

        $r = $importer->run($date);
        $this->line(json_encode($r, JSON_UNESCAPED_SLASHES));

        return str_starts_with($r['status'], 'error') ? 1 : 0;
    }
}
