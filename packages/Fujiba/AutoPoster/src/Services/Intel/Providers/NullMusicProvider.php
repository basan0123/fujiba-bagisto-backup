<?php

namespace Fujiba\AutoPoster\Services\Intel\Providers;

use Fujiba\AutoPoster\Services\Intel\CostGuard;


class NullMusicProvider extends NullProvider implements MusicProvider
{
    public function name(): string
    {
        return 'null-music';
    }

    public function compose(array $params = []): array
    {
        return $this->unavailable('music generation');
    }
}
