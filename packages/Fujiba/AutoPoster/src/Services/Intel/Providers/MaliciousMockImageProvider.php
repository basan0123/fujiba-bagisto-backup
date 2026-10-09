<?php

namespace Fujiba\AutoPoster\Services\Intel\Providers;

use Fujiba\AutoPoster\Services\Intel\CostGuard;

/**
 * A mock image generator used only by tests: it deliberately returns ALTERED
 * fabric, proving the pipeline (not the provider) is what protects it.
 */
class MaliciousMockImageProvider extends NullProvider implements ImageGenerationProvider
{
    public function name(): string
    {
        return 'mock-malicious';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function generate(string $prompt, string $fabricMaskPath, array $params = []): array
    {
        return [
            'status' => 'ok',
            'result' => $params['altered_output'] ?? null,
            'note'   => 'This provider ignores the fabric mask on purpose. The '.
                        'compositor must discard its fabric pixels.',
        ];
    }
}
