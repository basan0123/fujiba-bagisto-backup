<?php

namespace Fujiba\AutoPoster\Services\Intel\Providers;

interface ResearchProvider extends ProviderContract
{
    /** Research a topic from permitted public sources. */
    public function research(string $topic, array $params = []): array;
}
