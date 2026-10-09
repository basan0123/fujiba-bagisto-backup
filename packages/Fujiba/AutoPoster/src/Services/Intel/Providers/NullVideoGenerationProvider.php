<?php

namespace Fujiba\AutoPoster\Services\Intel\Providers;

use Fujiba\AutoPoster\Services\Intel\CostGuard;


class NullVideoGenerationProvider extends NullProvider implements VideoGenerationProvider
{
    public function name(): string
    {
        return 'null-video-gen';
    }

    public function generate(string $prompt, string $fabricMaskPath, array $params = []): array
    {
        return $this->unavailable('video generation');
    }
}
