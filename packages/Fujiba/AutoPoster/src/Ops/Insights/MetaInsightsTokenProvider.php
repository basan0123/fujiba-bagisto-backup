<?php

namespace Fujiba\AutoPoster\Ops\Insights;

interface MetaInsightsTokenProvider
{
    /** A read-only (insights) token, or null when none is configured. */
    public function token(): ?string;
}
