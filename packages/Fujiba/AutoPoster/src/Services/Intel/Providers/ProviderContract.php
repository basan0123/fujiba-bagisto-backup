<?php

namespace Fujiba\AutoPoster\Services\Intel\Providers;

/**
 * Provider abstraction for the intelligence layer.
 *
 * Every provider is OFF by default and every implementation in Phase 1 is a
 * null/mock. Nothing here calls a paid service.
 *
 * The rule that matters most is on ImageGenerationProvider: a provider may
 * ignore its prompt, so fabric protection is never delegated to it. Generated
 * output is untrusted, the fabric mask region is always discarded by
 * bin/intel/compositor.py, and the real photograph is composited back in before
 * the integrity gate runs.
 */
interface ProviderContract
{
    public function name(): string;

    public function model(): ?string;

    /** Estimated cost in JPY for the given operation, before it runs. */
    public function estimateCost(array $params): float;

    /** True when real credentials exist; false means the null path is active. */
    public function isConfigured(): bool;
}
