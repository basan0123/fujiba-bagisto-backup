<?php

namespace Fujiba\AutoPoster\Tests\Unit\Ops;

use Fujiba\AutoPoster\Ops\Console\IntelHealthCommand;
use Fujiba\AutoPoster\Ops\Health\HealthEvaluator;
use Fujiba\AutoPoster\Ops\Health\SystemProbes;
use PHPUnit\Framework\TestCase;

class HealthEvaluatorTest extends TestCase
{
    private function healthy(): array
    {
        return [
            'scheduler' => ['autoposter-dispatch' => ['age_min' => 3], 'autoposter-plan' => ['age_min' => 100]],
            'supervisor' => ['bagisto-worker:bagisto-worker_00' => ['state' => 'RUNNING'], 'bagisto-worker:bagisto-worker_01' => ['state' => 'RUNNING']],
            'queues' => ['default' => 0, 'insights' => 2],
            'failed_jobs' => ['total' => 10, 'last_24h' => 0],
            'posts' => ['last_fb_post' => date('Y-m-d H:i:s', time() - 3600), 'last_ig_post' => null],
            'system' => ['load' => [1.1, 1.0, 0.9], 'disk_free_pct' => 40.0, 'mem_avail_pct' => 50.0],
        ];
    }

    public function test_healthy_snapshot_is_ok(): void
    {
        $r = (new HealthEvaluator())->evaluate($this->healthy());
        $this->assertSame('ok', $r['status'], implode("\n", $r['issues']));
    }

    public function test_stopped_worker_is_crit(): void
    {
        $s = $this->healthy();
        $s['supervisor']['bagisto-worker:bagisto-worker_01']['state'] = 'FATAL';
        $this->assertSame('crit', (new HealthEvaluator())->evaluate($s)['status']);
    }

    public function test_warns_on_stale_scheduler_failed_jobs_load_and_old_post(): void
    {
        $s = $this->healthy();
        $s['scheduler']['autoposter-dispatch']['age_min'] = 90;
        $s['failed_jobs']['last_24h'] = 9;
        $s['system']['load'][0] = 5.0;
        $s['posts']['last_fb_post'] = date('Y-m-d H:i:s', time() - 72 * 3600);
        $r = (new HealthEvaluator())->evaluate($s);
        $this->assertSame('warn', $r['status']);
        $this->assertCount(4, $r['issues']);
    }

    public function test_probe_error_is_reported_not_fatal(): void
    {
        $s = $this->healthy();
        $s['queues'] = ['error' => 'redis down'];
        $r = (new HealthEvaluator())->evaluate($s);
        $this->assertSame('warn', $r['status']);
        $this->assertStringContainsString('redis down', $r['issues'][0]);
    }

    public function test_parsers_and_summary_line(): void
    {
        $p = SystemProbes::parseSupervisor("bagisto-worker:bagisto-worker_00   RUNNING   pid 1, uptime 1:00:00\nfoo   STOPPED   Not started\n");
        $this->assertSame('RUNNING', $p['bagisto-worker:bagisto-worker_00']['state']);
        $this->assertSame('STOPPED', $p['foo']['state']);
        $this->assertSame(['total' => 1000, 'avail' => 250], SystemProbes::meminfo("MemTotal: 1000 kB\nMemFree: 1 kB\nMemAvailable: 250 kB\n"));

        $line = IntelHealthCommand::summaryLine(['at' => 'T', 'status' => 'ok', 'issues' => []] + $this->healthy());
        $this->assertStringContainsString('status=ok workers_running=2/2', $line);
        $this->assertStringContainsString('insights=2', $line);
    }
}
