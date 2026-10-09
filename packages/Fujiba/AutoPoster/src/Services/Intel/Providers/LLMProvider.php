<?php

namespace Fujiba\AutoPoster\Services\Intel\Providers;

interface LLMProvider extends ProviderContract
{
    public function complete(string $system, string $prompt, array $params = []): array;
}
