<?php

namespace Fujiba\AutoPoster\Services\Intel\Providers;

use Fujiba\AutoPoster\Services\Intel\CostGuard;


class NullLLMProvider extends NullProvider implements LLMProvider
{
    public function name(): string
    {
        return 'null-llm';
    }

    public function complete(string $system, string $prompt, array $params = []): array
    {
        return $this->unavailable('text generation');
    }
}
