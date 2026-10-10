<?php

namespace Fujiba\AutoPoster\Ops\Console;

use Fujiba\AutoPoster\Ops\Health\HealthEvaluator;
use Fujiba\AutoPoster\Ops\Health\SystemProbes;
use Illuminate\Console\Command;

/**
 * Read-only health snapshot. Never sends messages.
 *   --heartbeat  write {log_dir}/fujiba-intel-heartbeat.json
 *   --daily      append one-line summary to {log_dir}/fujiba-intel-status.log
 *   --json       print JSON instead of the table
 * Exit: 0 ok, 1 warn, 2 crit.
 */
class IntelHealthCommand extends Command
{
    protected $signature = 'fujiba:intel:health {--json} {--heartbeat} {--daily} {--log-dir=} {--out-dir=} {--tenant=}';

    protected $description = 'Fujiba intelligence-layer health: scheduler, workers, queues, failed jobs, last posts, disk/RAM/load';

    public function handle(): int
    {
        $logDir = $this->option('log-dir') ?: (config('fujiba_intel.log_dir') ?: storage_path('logs'));
        $outDir = $this->option('out-dir') ?: $logDir;
        $tenant = $this->option('tenant') ?: config('fujiba_intel.tenant_id');

        $snap = app()->makeWith(SystemProbes::class, [
            'logDir' => $logDir, 'schedulerLogs' => (array) config('fujiba_intel.scheduler_logs', []), 'tenantId' => $tenant,
        ])->all();
        $eval = (new HealthEvaluator(['max_load' => config('fujiba_intel.guard.max_load', 4.2)]))->evaluate($snap);
        $report = ['at' => now()->toIso8601String(), 'status' => $eval['status'], 'issues' => $eval['issues']] + $snap;

        if ($this->option('heartbeat')) {
            @file_put_contents($outDir.'/fujiba-intel-heartbeat.json', json_encode($report, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        }
        if ($this->option('daily')) {
            @file_put_contents($outDir.'/fujiba-intel-status.log', self::summaryLine($report).PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } else {
            $this->line(self::summaryLine($report));
            foreach ($eval['issues'] as $i) {
                $this->line('  '.$i);
            }
        }

        return ['ok' => 0, 'warn' => 1, 'crit' => 2][$eval['status']];
    }

    public static function summaryLine(array $r): string
    {
        $q = is_array($r['queues'] ?? null) && ! isset($r['queues']['error'])
            ? implode(' ', array_map(fn ($k, $v) => "$k=$v", array_keys($r['queues']), $r['queues'])) : 'queues=?';
        $workers = isset($r['supervisor']['error']) ? '?' : count(array_filter($r['supervisor'] ?? [], fn ($p) => ($p['state'] ?? '') === 'RUNNING')).'/'.count($r['supervisor'] ?? []);
        $l = $r['system']['load'] ?? [];

        return sprintf('%s status=%s workers_running=%s %s failed_24h=%s failed_total=%s last_fb=%s last_ig=%s load=%s disk_free=%s%% mem_avail=%s%% issues=%d',
            $r['at'], $r['status'], $workers, $q,
            $r['failed_jobs']['last_24h'] ?? '?', $r['failed_jobs']['total'] ?? '?',
            $r['posts']['last_fb_post'] ?? '-', $r['posts']['last_ig_post'] ?? '-',
            implode('/', array_map(fn ($v) => $v ?? '?', $l)),
            $r['system']['disk_free_pct'] ?? '?', $r['system']['mem_avail_pct'] ?? '?', count($r['issues'] ?? []));
    }
}
