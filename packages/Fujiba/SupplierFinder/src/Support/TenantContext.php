<?php

namespace Fujiba\SupplierFinder\Support;

use App\Models\Tenant;
use RuntimeException;

/** Supplier Finder tables live only in the kimono tenant DB. */
class TenantContext
{
    public static function enter(): void
    {
        $id = (string) config('supplier-finder.tenant_id');

        if (tenancy()->initialized) {
            if ((string) tenancy()->tenant->getTenantKey() !== $id) {
                throw new RuntimeException('supplier-finder only runs for the kimono tenant '.$id);
            }

            return;
        }
        tenancy()->initialize(Tenant::find($id) ?? throw new RuntimeException("Tenant {$id} not found"));
    }

    /** Private working dir (never web-served). base_path() so tenancy's storage suffixing can't move it. */
    public static function dir(string $sub = ''): string
    {
        $d = base_path('storage/app/'.config('supplier-finder.storage_dir')).($sub ? '/'.$sub : '');
        if (! is_dir($d)) {
            mkdir($d, 0775, true);
        }

        return $d;
    }
}
