<?php

namespace Fujiba\AutoPoster\Services\Intel\Providers;

use Fujiba\AutoPoster\Services\Intel\CostGuard;


class NullResearchProvider extends NullProvider implements ResearchProvider
{
    public function name(): string
    {
        return 'null-research';
    }

    public function research(string $topic, array $params = []): array
    {
        return $this->unavailable('web research');
    }
}
