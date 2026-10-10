<?php

namespace Fujiba\SupplierFinder\Models;

use Illuminate\Database\Eloquent\Model;

class CrawlRun extends Model
{
    protected $table = 'sf_crawl_runs';

    protected $guarded = ['id'];

    protected $casts = ['counts' => 'array', 'diff' => 'array', 'finished_at' => 'datetime'];

}
