<?php

namespace Fujiba\AutoPoster\Ops\Health;

use Fujiba\AutoPoster\Ops\IntelQueues;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Read-only probes. Every probe catches its own failure and reports
 * ['error' => ...] so one broken source never hides the rest.
 */
class SystemProbes
{
    public function __construct(private string $logDir, private array $schedulerLogs, private ?string $tenantId = null) {}

    public function all(): array
    {
        return [
            'scheduler'  => $this->safe(fn () => $this->scheduler()),
            'supervisor' => $this->safe(fn () => $this->supervisor()),
            'queues'     => $this->safe(fn () => $this->queues()),
            'failed_jobs'=> $this->safe(fn () => $this->failedJobs()),
            'posts'      => $this->safe(fn () => $this->posts()),
            'system'     => $this->safe(fn () => $this->system()),
        ];
    }

    /** Last run per scheduled task, from its appendOutputTo log (mtime + last line). */
    public function scheduler(): array
    {
        $out = [];
        foreach ($this->schedulerLogs as $name => $file) {
            $path = $this->logDir.'/'.$file;
            if (! is_file($path)) {
                $out[$name] = ['last_run' => null, 'age_min' => null, 'last_line' => null];
                continue;
            }
            $m = filemtime($path);
            $out[$name] = [
                'last_run'  => date('c', $m),
                'age_min'   => (int) round((time() - $m) / 60),
                'last_line' => self::lastLine($path),
            ];
        }

        return $out;
    }

    public function supervisor(): array
    {
        $p = new Process(['supervisorctl', 'status']);
        $p->setTimeout(10);
        $p->run(); // exit code 3 when any program is not RUNNING - still parse

        return self::parseSupervisor($p->getOutput());
    }

    public static function parseSupervisor(string $output): array
    {
        $procs = [];
        foreach (preg_split('/\R/', trim($output)) as $line) {
            if (preg_match('/^(\S+)\s+([A-Z]+)\s*(.*)$/', trim($line), $m)) {
                $procs[$m[1]] = ['state' => $m[2], 'info' => trim($m[3])];
            }
        }

        return $procs;
    }

    public function queues(): array
    {
        $out = [];
        foreach (array_merge(IntelQueues::SHOP, IntelQueues::ALL) as $q) {
            $out[$q] = Queue::connection('redis')->size($q);
        }

        return $out;
    }

    public function failedJobs(): array
    {
        $c = DB::connection(config('queue.failed.database') ?: config('database.default'));
        $t = config('queue.failed.table', 'failed_jobs');

        return [
            'total'    => $c->table($t)->count(),
            'last_24h' => $c->table($t)->where('failed_at', '>=', now()->subDay())->count(),
        ];
    }

    public function posts(): array
    {
        $q = fn () => [
            'last_fb_post' => DB::table('autoposter_queue')->whereNotNull('facebook_post_id')->max('published_at'),
            'last_ig_post' => Schema::hasColumn('autoposter_queue', 'instagram_media_id')
                ? DB::table('autoposter_queue')->whereNotNull('instagram_media_id')->max('instagram_published_at') : null,
        ];

        if ($this->tenantId && function_exists('tenancy') && ! tenancy()->initialized) {
            $tenant = tenancy()->find($this->tenantId);

            return $tenant ? $tenant->run($q) : ['error' => 'tenant not found'];
        }

        return $q();
    }

    public function system(): array
    {
        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : [null, null, null];
        $total = @disk_total_space('/') ?: 0;
        $free = @disk_free_space('/') ?: 0;
        $mem = self::meminfo(@file_get_contents('/proc/meminfo') ?: '');

        return [
            'load'          => array_map(fn ($v) => $v === null ? null : round($v, 2), $load),
            'cores'         => (int) (trim((string) @shell_exec('nproc')) ?: 0),
            'disk_free_pct' => $total ? round($free / $total * 100, 1) : null,
            'disk_free_gb'  => round($free / 1073741824, 1),
            'mem_avail_pct' => $mem['total'] ? round($mem['avail'] / $mem['total'] * 100, 1) : null,
            'mem_avail_mb'  => (int) round($mem['avail'] / 1024),
        ];
    }

    public static function meminfo(string $raw): array
    {
        preg_match('/MemTotal:\s+(\d+)/', $raw, $t);
        preg_match('/MemAvailable:\s+(\d+)/', $raw, $a);

        return ['total' => (int) ($t[1] ?? 0), 'avail' => (int) ($a[1] ?? 0)];
    }

    public static function lastLine(string $path): ?string
    {
        $fh = @fopen($path, 'r');
        if (! $fh) {
            return null;
        }
        $size = filesize($path);
        fseek($fh, max(0, $size - 4096));
        $chunk = stream_get_contents($fh);
        fclose($fh);
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $chunk))));

        return $lines ? mb_substr(end($lines), 0, 200) : null;
    }

    private function safe(callable $fn): array
    {
        try {
            return $fn();
        } catch (Throwable $e) {
            return ['error' => mb_substr($e->getMessage(), 0, 200)];
        }
    }
}
