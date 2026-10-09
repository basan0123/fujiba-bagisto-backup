<?php

namespace Fujiba\AutoPoster\Services\Intel\Providers;

/**
 * Phase 1 providers: null implementations only. No paid service is called.
 *
 * Each returns status=capability_limited with the reason, rather than empty
 * output that could be mistaken for a real negative result.
 */
abstract class NullProvider implements ProviderContract
{
    public function model(): ?string
    {
        return null;
    }

    public function estimateCost(array $params): float
    {
        return 0.0;
    }

    public function isConfigured(): bool
    {
        return false;
    }

    protected function unavailable(string $what): array
    {
        return [
            'status' => 'capability_limited',
            'result' => null,
            'note'   => $what.' needs a provider; none is configured in Phase 1 '.
                        '(no paid APIs). Nothing was generated or inferred.',
        ];
    }
}
