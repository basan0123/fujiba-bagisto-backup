<?php

namespace Fujiba\AutoPoster\Models\Intel;

use Illuminate\Database\Eloquent\Model;

/**
 * H — Marketing memory. Audience/creative learnings only; product facts live
 * in fi_facts.
 *
 * The point of this table is that an AI-written idea must never quietly become
 * permanent truth. Everything the agent proposes enters as HYPOTHESIS and can
 * only be promoted by evidence that passes config('fujiba_intel.evidence').
 */
class MarketingMemory extends Model
{
    protected $table = 'fi_marketing_memory';

    public const KIND_OBSERVATION = 'observation';
    public const KIND_HYPOTHESIS = 'hypothesis';
    public const KIND_EXPERIMENT_RESULT = 'experiment_result';
    public const KIND_CONFIRMED = 'confirmed_learning';

    /** The only legal promotion path. */
    public const LADDER = [
        self::KIND_OBSERVATION,
        self::KIND_HYPOTHESIS,
        self::KIND_EXPERIMENT_RESULT,
        self::KIND_CONFIRMED,
    ];

    protected $fillable = [
        'audience', 'language', 'statement', 'kind', 'evidence', 'confidence',
        'supporting_creative_ids', 'supporting_order_ids', 'sample_size', 'expires_at',
    ];

    protected $casts = [
        'evidence'                => 'array',
        'supporting_creative_ids' => 'array',
        'supporting_order_ids'    => 'array',
        'confidence'              => 'float',
        'sample_size'             => 'integer',
        'expires_at'              => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Usable as a basis for a creative decision? Expired or unconfirmed
     * learnings may still be *cited*, but callers must show their kind.
     */
    public function isActionable(): bool
    {
        return $this->kind === self::KIND_CONFIRMED && ! $this->isExpired();
    }
}
