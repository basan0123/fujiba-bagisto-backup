<?php

namespace Fujiba\AutoPoster\Services\Intel\Providers;

use Fujiba\AutoPoster\Services\Intel\CostGuard;


class NullSpeechProvider extends NullProvider implements SpeechProvider
{
    public function name(): string
    {
        return 'null-speech';
    }

    public function speak(string $text, array $params = []): array
    {
        return $this->unavailable('speech synthesis');
    }
}
