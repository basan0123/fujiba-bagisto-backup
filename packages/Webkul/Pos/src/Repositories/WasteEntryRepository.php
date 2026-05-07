<?php

namespace Webkul\Pos\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Pos\Contracts\WasteEntry;

class WasteEntryRepository extends Repository
{
    public function model(): string
    {
        return WasteEntry::class;
    }
}
