<?php

namespace Fujiba\AutoPoster\Services\Intel\Providers;

use Fujiba\AutoPoster\Services\Intel\CostGuard;


class NullWebVisualSearchProvider extends NullProvider implements WebVisualSearchProvider
{
    public function name(): string
    {
        return 'null-visual-search';
    }

    public function searchByImage(string $imagePath, array $params = []): array
    {
        return [
            'status'  => 'capability_limited',
            'results' => [],
            'note'    => 'No visual-search API is configured, and scraping search '.
                         'engines or social platforms is forbidden by the research '.
                         'rules. This is NOT "no matches found" — no search ran. '.
                         'Local image-to-image comparison still works via '.
                         'VisualEvidenceService::compare().',
        ];
    }
}

/**
 * A mock image generator used only by tests: it deliberately returns ALTERED
 * fabric, proving the pipeline (not the provider) is what protects it.
 */
