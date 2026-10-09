<?php

namespace Fujiba\AutoPoster\Services\Intel\Providers;

interface VideoGenerationProvider extends ProviderContract
{
    public function generate(string $prompt, string $fabricMaskPath, array $params = []): array;
}
