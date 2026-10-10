<?php

namespace Fujiba\SupplierFinder\Models;

use Illuminate\Database\Eloquent\Model;

class SourceShop extends Model
{
    protected $table = 'sf_source_shops';

    protected $guarded = ['id'];

    protected $casts = ['start_urls' => 'array', 'strip_prefixes' => 'array', 'enabled' => 'bool', 'last_crawled_at' => 'datetime'];

    public function products()
    {
        return $this->hasMany(CrawledProduct::class, 'shop_id');
    }
}
