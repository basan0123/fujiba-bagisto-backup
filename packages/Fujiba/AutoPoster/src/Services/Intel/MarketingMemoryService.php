<?php

namespace Fujiba\AutoPoster\Services\Intel;

use Carbon\Carbon;
use Fujiba\AutoPoster\Models\Intel\MarketingMemory;
use Illuminate\Support\Facades\Log;

/**
 * H — evidence rules for marketing memory.
 *
 * Two invariants this class exists to protect:
 *
 *   1. Anything the agent writes enters as a HYPOTHESIS. There is no path for
 *      AI output to be created as a confirmed_learning, which is why
 *      recordHypothesis() hard-codes the kind instead of accepting it.
 *   2. Promotion requires evidence meeting every configured threshold. A
 *      caller cannot promote by asking nicely; it has to supply numbers.
 */
class MarketingMemoryService
{
    /**
     * Record an AI- or human-proposed idea. Always a hypothesis.
     */
    public function recordHypothesis(
        string $statement,
        ?string $audience = null,
        ?string $language = null,
        array $evidence = [],
        float $confidence = 0.1,
    ): MarketingMemory {
        $ttl = (int) config('fujiba_intel.evidence.default_ttl_days', 180);

        return MarketingMemory::create([
            'statement'  => $statement,
            'audience'   => $audience,
            'language'   => $language,
            // Deliberately not a parameter — see the class docblock.
            'kind'       => MarketingMemory::KIND_HYPOTHESIS,
            'evidence'   => $evidence,
            'confidence' => max(0.0, min(1.0, $confidence)),
            'expires_at' => Carbon::now()->addDays($ttl),
        ]);
    }

    /**
     * An observation is a measured statement of what happened — no claim about
     * why. Lower rung than a hypothesis.
     */
    public function recordObservation(string $statement, array $evidence = [], ?string $audience = null): MarketingMemory
    {
        return MarketingMemory::create([
            'statement'  => $statement,
            'audience'   => $audience,
            'kind'       => MarketingMemory::KIND_OBSERVATION,
            'evidence'   => $evidence,
            'confidence' => 0.0,
            'expires_at' => Carbon::now()->addDays((int) config('fujiba_intel.evidence.default_ttl_days', 180)),
        ]);
    }

    /**
     * Would this evidence promote the row? Returns the reasons it would not,
     * so the caller (and `intel:explain`) can say exactly what is missing.
     *
     * @param  array  $evidence  experiments, clicks_per_arm, attributed_orders, win_probability
     * @return array{eligible:bool,failures:array<int,string>,next_kind:?string}
     */
    public function evaluatePromotion(MarketingMemory $memory, array $evidence): array
    {
        $cfg = config('fujiba_intel.evidence');
        $failures = [];

        $experiments = (int) ($evidence['experiments'] ?? 0);
        $clicks      = (int) ($evidence['clicks_per_arm'] ?? 0);
        $orders      = (int) ($evidence['attributed_orders'] ?? 0);
        $winProb     = (float) ($evidence['win_probability'] ?? 0.0);

        if ($experiments < (int) $cfg['min_experiments']) {
            $failures[] = "needs {$cfg['min_experiments']} independent experiments, has {$experiments}";
        }

        if ($clicks < (int) $cfg['min_clicks_per_arm']) {
            $failures[] = "needs {$cfg['min_clicks_per_arm']} clicks per arm, has {$clicks}";
        }

        if ($orders < (int) $cfg['min_attributed_orders']) {
            $failures[] = "needs {$cfg['min_attributed_orders']} attributed orders, has {$orders}";
        }

        if ($winProb < (float) $cfg['min_win_probability']) {
            $failures[] = "needs win probability {$cfg['min_win_probability']}, has {$winProb}";
        }

        // Likes/reactions never decide anything — guard against a caller that
        // tries to pass engagement off as evidence.
        if (empty($evidence) || (! isset($evidence['attributed_orders']) && isset($evidence['likes']))) {
            $failures[] = 'likes/reactions are not evidence; orders or clicks required';
        }

        $next = null;
        if (! $failures) {
            $i = array_search($memory->kind, MarketingMemory::LADDER, true);
            $next = MarketingMemory::LADDER[min($i + 1, count(MarketingMemory::LADDER) - 1)] ?? null;
        }

        return ['eligible' => empty($failures), 'failures' => $failures, 'next_kind' => $next];
    }

    /**
     * Promote one rung, only when the evidence passes. Never skips rungs.
     */
    public function promote(MarketingMemory $memory, array $evidence): bool
    {
        $verdict = $this->evaluatePromotion($memory, $evidence);

        if (! $verdict['eligible']) {
            Log::info('[intel] promotion refused', [
                'memory_id' => $memory->id,
                'failures'  => $verdict['failures'],
            ]);

            return false;
        }

        $memory->update([
            'kind'        => $verdict['next_kind'],
            'evidence'    => array_merge($memory->evidence ?? [], $evidence),
            'confidence'  => (float) ($evidence['win_probability'] ?? $memory->confidence),
            'sample_size' => (int) ($evidence['attributed_orders'] ?? $memory->sample_size),
            'expires_at'  => Carbon::now()->addDays((int) config('fujiba_intel.evidence.default_ttl_days', 180)),
        ]);

        return true;
    }

    /**
     * Contradicting evidence lowers confidence and can demote a confirmed
     * learning back to a hypothesis — the system must be able to change its
     * mind when reality does.
     */
    public function recordContradiction(MarketingMemory $memory, array $evidence, float $penalty = 0.3): MarketingMemory
    {
        $confidence = max(0.0, $memory->confidence - $penalty);

        $kind = $memory->kind;
        if ($kind === MarketingMemory::KIND_CONFIRMED && $confidence < (float) config('fujiba_intel.evidence.min_win_probability')) {
            $kind = MarketingMemory::KIND_HYPOTHESIS;
        }

        $memory->update([
            'kind'       => $kind,
            'confidence' => $confidence,
            'evidence'   => array_merge($memory->evidence ?? [], ['contradiction' => $evidence]),
        ]);

        return $memory->refresh();
    }

    /** Learnings safe to act on right now. */
    public function actionable(?string $audience = null)
    {
        return MarketingMemory::where('kind', MarketingMemory::KIND_CONFIRMED)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now()))
            ->when($audience, fn ($q) => $q->where('audience', $audience))
            ->get();
    }
}
