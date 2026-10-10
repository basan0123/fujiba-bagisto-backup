<?php

namespace Fujiba\AutoPoster\Services\Intel;

/**
 * Supplies per-image fingerprints measured by the catalog inventory (WS-B).
 * Each row: ['product_id' => int, 'sha256' => ?string, 'dhash' => ?string (16 hex)].
 */
interface DuplicateImageSource
{
    /** @return iterable<array{product_id:int,sha256:?string,dhash:?string}> */
    public function rows(): iterable;
}
