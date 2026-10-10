<?php

namespace Fujiba\AutoPoster\Ops;

/**
 * Dedicated queues for the intelligence layer, kept apart from the shop's
 * `default,broadcastable,tiktok` queues so a heavy job can never starve
 * order notifications. Each queue gets exactly ONE worker (see
 * deploy/supervisor/fujiba-intel-workers.conf).
 */
final class IntelQueues
{
    public const HEAVY = 'intel-heavy';   // image analysis, rendering, CV
    public const RESEARCH = 'research';   // crawling / research fetches
    public const INSIGHTS = 'insights';   // Meta Insights import (GET only)

    public const ALL = [self::HEAVY, self::RESEARCH, self::INSIGHTS];

    /** Queues the shop workers already drain (for health depth reporting). */
    public const SHOP = ['default', 'broadcastable', 'tiktok'];
}
