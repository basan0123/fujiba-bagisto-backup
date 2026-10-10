<?php

namespace Fujiba\AutoPoster\Services\Intel;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Read-only: fi_catalog_images in the current tenant. Missing table => no rows. */
class DbDuplicateImageSource implements DuplicateImageSource
{
    public function rows(): iterable
    {
        if (! Schema::hasTable('fi_catalog_images')) {
            return [];
        }

        return DB::table('fi_catalog_images')
            ->where(fn ($q) => $q->whereNotNull('sha256')->orWhereNotNull('dhash'))
            ->get(['product_id', 'sha256', 'dhash'])
            ->map(fn ($r) => ['product_id' => (int) $r->product_id, 'sha256' => $r->sha256, 'dhash' => $r->dhash])
            ->all();
    }
}
