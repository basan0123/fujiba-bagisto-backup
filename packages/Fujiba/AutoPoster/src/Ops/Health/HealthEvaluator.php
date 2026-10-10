<?php

namespace Fujiba\AutoPoster\Ops\Health;

/** Pure: turns a probe snapshot into ok|warn|crit + human issues. */
class HealthEvaluator
{
    public function __construct(private array $t = [])
    {
        $this->t += [
            'max_load'              => 4.2,
            'min_disk_free_pct'     => 10,
            'min_mem_avail_pct'     => 10,
            'failed_jobs_24h_warn'  => 5,
            'scheduler_stale_min'   => ['autoposter-dispatch' => 30, 'autoposter-instagram' => 30, 'autoposter-plan' => 26 * 60],
            'fb_post_stale_hours'   => 48,
            'queue_depth_warn'      => 500,
            'workers'               => ['bagisto-worker'],
        ];
    }

    public function evaluate(array $s): array
    {
        $issues = [];

        foreach ($s as $k => $v) {
            if (isset($v['error'])) {
                $issues[] = ['warn', "$k probe error: {$v['error']}"];
            }
        }

        foreach ($this->t['workers'] as $prog) {
            $procs = array_filter($s['supervisor'] ?? [], fn ($v, $k) => str_starts_with($k, $prog.':') || $k === $prog, ARRAY_FILTER_USE_BOTH);
            if (! isset($s['supervisor']['error']) && ! $procs) {
                $issues[] = ['crit', "no supervisor processes for $prog"];
            }
            foreach ($procs as $name => $p) {
                if (($p['state'] ?? '') !== 'RUNNING') {
                    $issues[] = ['crit', "$name is {$p['state']}"];
                }
            }
        }

        foreach ($this->t['scheduler_stale_min'] as $name => $max) {
            $age = $s['scheduler'][$name]['age_min'] ?? null;
            if (array_key_exists($name, $s['scheduler'] ?? []) && ($age === null || $age > $max)) {
                $issues[] = ['warn', "scheduler $name last ran ".($age === null ? 'never' : "{$age}m ago")." (> {$max}m)"];
            }
        }

        foreach (($s['queues'] ?? []) as $q => $n) {
            if (is_int($n) && $n > $this->t['queue_depth_warn']) {
                $issues[] = ['warn', "queue $q depth $n"];
            }
        }

        if (($s['failed_jobs']['last_24h'] ?? 0) > $this->t['failed_jobs_24h_warn']) {
            $issues[] = ['warn', "failed_jobs last 24h: {$s['failed_jobs']['last_24h']}"];
        }

        $fb = $s['posts']['last_fb_post'] ?? null;
        if (! isset($s['posts']['error']) && (! $fb || strtotime($fb) < time() - $this->t['fb_post_stale_hours'] * 3600)) {
            $issues[] = ['warn', 'no FB post in '.$this->t['fb_post_stale_hours'].'h'];
        }

        $load1 = $s['system']['load'][0] ?? null;
        if ($load1 !== null && $load1 > $this->t['max_load']) {
            $issues[] = ['warn', "load $load1 > {$this->t['max_load']}"];
        }
        if (($d = $s['system']['disk_free_pct'] ?? null) !== null && $d < $this->t['min_disk_free_pct']) {
            $issues[] = ['crit', "disk free {$d}%"];
        }
        if (($m = $s['system']['mem_avail_pct'] ?? null) !== null && $m < $this->t['min_mem_avail_pct']) {
            $issues[] = ['warn', "mem available {$m}%"];
        }

        $levels = array_column($issues, 0);
        $status = in_array('crit', $levels, true) ? 'crit' : (in_array('warn', $levels, true) ? 'warn' : 'ok');

        return ['status' => $status, 'issues' => array_map(fn ($i) => "[{$i[0]}] {$i[1]}", $issues)];
    }
}
