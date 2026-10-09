<?php

namespace Fujiba\AutoPoster\Services\Intel\Providers;

interface VisionProvider extends ProviderContract
{
    /** Describe an image (motifs, watermark, packaging). */
    public function analyse(string $imagePath, array $params = []): array;
}
