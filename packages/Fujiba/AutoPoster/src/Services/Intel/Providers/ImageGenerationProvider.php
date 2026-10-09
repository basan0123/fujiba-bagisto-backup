<?php

namespace Fujiba\AutoPoster\Services\Intel\Providers;

interface ImageGenerationProvider extends ProviderContract
{
    /**
     * Generate the world AROUND the fabric.
     *
     * $fabricMaskPath is REQUIRED: implementations must treat that region as
     * off-limits, and the pipeline discards whatever they return inside it
     * regardless. There is no signature here that permits generating fabric.
     */
    public function generate(string $prompt, string $fabricMaskPath, array $params = []): array;
}
