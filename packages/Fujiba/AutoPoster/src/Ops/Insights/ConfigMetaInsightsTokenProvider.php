<?php

namespace Fujiba\AutoPoster\Ops\Insights;

/**
 * Reads config('fujiba_intel.meta_insights_token') - a key that does NOT exist
 * yet. Until the owner provisions a dedicated read-only token there, this
 * returns null and the importer reports `blocked:no_token`. Deliberately does
 * not fall back to any other token source.
 */
class ConfigMetaInsightsTokenProvider implements MetaInsightsTokenProvider
{
    public function token(): ?string
    {
        $t = config('fujiba_intel.meta_insights_token');

        return is_string($t) && trim($t) !== '' ? trim($t) : null;
    }
}
