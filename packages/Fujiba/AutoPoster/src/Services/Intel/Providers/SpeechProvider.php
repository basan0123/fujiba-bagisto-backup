<?php

namespace Fujiba\AutoPoster\Services\Intel\Providers;

interface SpeechProvider extends ProviderContract
{
    public function speak(string $text, array $params = []): array;
}
