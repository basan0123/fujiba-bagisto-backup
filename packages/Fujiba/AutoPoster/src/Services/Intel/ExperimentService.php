<?php

namespace Fujiba\AutoPoster\Services\Intel;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * I — experiments / A-B.
 *
 * Two things this class refuses to do:
 *
 *   1. Decide a winner from likes. The metric hierarchy is
 *      profit > revenue > orders > clicks, and engagement is not in it.
 *   2. Call a winner on thin data. Below the configured thresholds the
 *      experiment concludes as `insufficient_data` with winner = null.
 *
 * Win probability is a beta-binomial estimate: each arm's conversion rate is
 * modelled Beta(1 + successes, 1 + failures) — a uniform prior, so no arm is
 * favoured — and P(variant > control) is taken by Monte Carlo with a fixed
 * seed, which keeps the result reproducible for the same inputs.
 */
class ExperimentService
{
    public const SAMPLES = 20000;

    /** Metric hierarchy. Likes/reactions deliberately absent. */
    public const METRIC_ORDER = ['profit', 'revenue', 'orders', 'clicks'];

    public function create(array $attrs): int
    {
        return DB::table('fi_experiments')->insertGetId([
            'name'                 => $attrs['name'],
            'hypothesis_id'        => $attrs['hypothesis_id'] ?? null,
            'variable_tested'      => $attrs['variable_tested'],
            'control_creative_id'  => $attrs['control_creative_id'] ?? null,
            'variant_creative_ids' => json_encode($attrs['variant_creative_ids'] ?? []),
            'product_id'           => $attrs['product_id'] ?? null,
            'audience'             => $attrs['audience'] ?? null,
            'language'             => $attrs['language'] ?? null,
            'platform'             => $attrs['platform'] ?? null,
            'design'               => $attrs['design'] ?? 'alternating_time',
            'start_at'             => $attrs['start_at'] ?? Carbon::now(),
            'status'               => 'running',
            'created_at'           => Carbon::now(),
            'updated_at'           => Carbon::now(),
        ]);
    }

    public function recordArm(int $experimentId, string $arm, array $metrics): void
    {
        DB::table('fi_experiment_arms')->updateOrInsert(
            ['experiment_id' => $experimentId, 'arm' => $arm],
            [
                'creative_id' => $metrics['creative_id'] ?? null,
                'impressions' => (int) ($metrics['impressions'] ?? 0),
                'clicks'      => (int) ($metrics['clicks'] ?? 0),
                'orders'      => (int) ($metrics['orders'] ?? 0),
                'revenue'     => (float) ($metrics['revenue'] ?? 0),
                'profit'      => $metrics['profit'] ?? null,
                'updated_at'  => Carbon::now(),
                'created_at'  => Carbon::now(),
            ]
        );
    }

    /**
     * Conclude an experiment. Returns the verdict without inventing one.
     *
     * @return array{status:string,winner:?string,win_probability:?float,metric:?string,reasons:array}
     */
    public function conclude(int $experimentId): array
    {
        $cfg = config('fujiba_intel.evidence');
        $arms = DB::table('fi_experiment_arms')->where('experiment_id', $experimentId)->get();

        if ($arms->count() < 2) {
            return $this->finish($experimentId, 'insufficient_data', null, null, null,
                ['need at least two arms, have '.$arms->count()]);
        }

        $reasons = [];

        // Gate on evidence before looking at any numbers, so a lucky-looking
        // split cannot tempt a premature call.
        $minClicks = (int) $cfg['min_clicks_per_arm'];
        foreach ($arms as $a) {
            if ((int) $a->clicks < $minClicks) {
                $reasons[] = "arm {$a->arm} has {$a->clicks} clicks, needs {$minClicks}";
            }
        }

        $totalOrders = (int) $arms->sum('orders');
        if ($totalOrders < (int) $cfg['min_attributed_orders']) {
            $reasons[] = "needs {$cfg['min_attributed_orders']} attributed orders, has {$totalOrders}";
        }

        if ($reasons) {
            return $this->finish($experimentId, 'insufficient_data', null, null, null, $reasons);
        }

        // Pick the highest metric for which every arm has data.
        $metric = $this->bestAvailableMetric($arms);
        if (! $metric) {
            return $this->finish($experimentId, 'insufficient_data', null, null, null,
                ['no arm has profit, revenue, orders or clicks data']);
        }

        $control = $arms->firstWhere('arm', 'control') ?? $arms->first();
        $best = null;
        $bestProb = 0.0;

        foreach ($arms as $a) {
            if ($a->id === $control->id) {
                continue;
            }
            $p = $this->winProbability($a, $control, $metric);
            if ($p > $bestProb) {
                $bestProb = $p;
                $best = $a;
            }
        }

        $threshold = (float) $cfg['min_win_probability'];

        if (! $best || $bestProb < $threshold) {
            return $this->finish($experimentId, 'insufficient_data', null, $bestProb, $metric,
                ["win probability {$bestProb} below required {$threshold}"]);
        }

        return $this->finish($experimentId, 'concluded', $best->arm, $bestProb, $metric, []);
    }

    /** Highest-priority metric that every arm actually carries. */
    protected function bestAvailableMetric($arms): ?string
    {
        foreach (self::METRIC_ORDER as $metric) {
            $usable = $arms->every(function ($a) use ($metric) {
                $v = $a->{$metric} ?? null;

                return $v !== null && (float) $v > 0;
            });

            if ($usable) {
                return $metric;
            }
        }

        // Clicks are the floor; anything below that is not evidence.
        return $arms->every(fn ($a) => (int) $a->clicks > 0) ? 'clicks' : null;
    }

    /**
     * P(arm beats control).
     *
     * Count metrics (orders, clicks) are genuinely binomial — k successes in n
     * impressions — so they use a Beta(1+k, 1+n-k) posterior.
     *
     * Money metrics are NOT binomial: profit per impression is unbounded, and
     * clamping yen into a success count saturates both arms at n/n and makes
     * a 4x winner look like a coin flip. They are modelled instead as a value
     * per converting order — the order count carries the uncertainty, and the
     * average value scales it.
     */
    protected function winProbability($arm, $control, string $metric): float
    {
        mt_srand(42); // reproducible for identical inputs

        return in_array($metric, ['profit', 'revenue'], true)
            ? $this->winProbabilityValue($arm, $control, $metric)
            : $this->winProbabilityCount($arm, $control, $metric);
    }

    /** Beta-binomial on a true count metric. */
    protected function winProbabilityCount($arm, $control, string $metric): float
    {
        $ta = (int) ($arm->impressions ?: $arm->clicks);
        $tc = (int) ($control->impressions ?: $control->clicks);

        if ($ta <= 0 || $tc <= 0) {
            return 0.0;
        }

        $sa = min((int) $arm->{$metric}, $ta);
        $sc = min((int) $control->{$metric}, $tc);

        $wins = 0;
        for ($i = 0; $i < self::SAMPLES; $i++) {
            if ($this->betaSample(1 + $sa, 1 + max(0, $ta - $sa))
                > $this->betaSample(1 + $sc, 1 + max(0, $tc - $sc))) {
                $wins++;
            }
        }

        return round($wins / self::SAMPLES, 4);
    }

    /**
     * Money per impression = (orders/impressions) × (value/order). The rate is
     * sampled from its Beta posterior; the per-order value is treated as
     * measured, so uncertainty comes from the conversion count, which is where
     * it actually lives at these sample sizes.
     */
    protected function winProbabilityValue($arm, $control, string $metric): float
    {
        $ta = (int) ($arm->impressions ?: $arm->clicks);
        $tc = (int) ($control->impressions ?: $control->clicks);

        if ($ta <= 0 || $tc <= 0) {
            return 0.0;
        }

        $oa = max(0, min((int) $arm->orders, $ta));
        $oc = max(0, min((int) $control->orders, $tc));

        // Without orders there is no conversion to model; fall back to clicks.
        if ($oa === 0 && $oc === 0) {
            return $this->winProbabilityCount($arm, $control, 'clicks');
        }

        $va = $oa > 0 ? (float) $arm->{$metric} / $oa : 0.0;
        $vc = $oc > 0 ? (float) $control->{$metric} / $oc : 0.0;

        $wins = 0;
        for ($i = 0; $i < self::SAMPLES; $i++) {
            $ra = $this->betaSample(1 + $oa, 1 + max(0, $ta - $oa)) * $va;
            $rc = $this->betaSample(1 + $oc, 1 + max(0, $tc - $oc)) * $vc;
            if ($ra > $rc) {
                $wins++;
            }
        }

        return round($wins / self::SAMPLES, 4);
    }

    /** Beta(a,b) via two gamma draws. */
    protected function betaSample(float $a, float $b): float
    {
        $x = $this->gammaSample($a);
        $y = $this->gammaSample($b);

        return ($x + $y) > 0 ? $x / ($x + $y) : 0.0;
    }

    /** Marsaglia-Tsang gamma sampler. */
    protected function gammaSample(float $shape): float
    {
        if ($shape < 1.0) {
            $u = mt_rand() / mt_getrandmax();

            return $this->gammaSample($shape + 1.0) * pow(max($u, 1e-12), 1.0 / $shape);
        }

        $d = $shape - 1.0 / 3.0;
        $c = 1.0 / sqrt(9.0 * $d);

        while (true) {
            do {
                $u1 = mt_rand() / mt_getrandmax();
                $u2 = mt_rand() / mt_getrandmax();
                $z = sqrt(-2.0 * log(max($u1, 1e-12))) * cos(2 * M_PI * $u2);
                $v = 1.0 + $c * $z;
            } while ($v <= 0);

            $v = $v * $v * $v;
            $u = mt_rand() / mt_getrandmax();

            if ($u < 1.0 - 0.0331 * pow($z, 4)) {
                return $d * $v;
            }

            if (log(max($u, 1e-12)) < 0.5 * $z * $z + $d * (1.0 - $v + log($v))) {
                return $d * $v;
            }
        }
    }

    protected function finish(int $id, string $status, ?string $winner, ?float $prob, ?string $metric, array $reasons): array
    {
        DB::table('fi_experiments')->where('id', $id)->update([
            'status'          => $status,
            'winner'          => $winner,
            'win_probability' => $prob,
            'end_at'          => Carbon::now(),
            'notes'           => $reasons ? implode('; ', $reasons) : ($metric ? "decided on {$metric}" : null),
            'updated_at'      => Carbon::now(),
        ]);

        return ['status' => $status, 'winner' => $winner, 'win_probability' => $prob,
            'metric' => $metric, 'reasons' => $reasons];
    }
}
