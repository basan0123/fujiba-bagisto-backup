<?php

namespace Fujiba\AutoPoster\Services\Intel\Providers;

use Fujiba\AutoPoster\Services\Intel\CostGuard;


class NullImageGenerationProvider extends NullProvider implements ImageGenerationProvider
{
    public function name(): string
    {
        return 'null-image-gen';
    }

    public function generate(string $prompt, string $fabricMaskPath, array $params = []): array
    {
        return $this->unavailable('image generation');
    }
}
