<?php

namespace Fujiba\AutoPoster\Services\Intel\Providers;

use Fujiba\AutoPoster\Services\Intel\CostGuard;


class NullVisionProvider extends NullProvider implements VisionProvider
{
    public function name(): string
    {
        return 'null-vision';
    }

    public function analyse(string $imagePath, array $params = []): array
    {
        // Local measurements (resolution, sharpness, colour) do NOT come
        // through here — see PhotoAuditService. This covers only the parts
        // that genuinely need a vision model.
        return $this->unavailable('watermark/packaging/motif recognition');
    }
}
