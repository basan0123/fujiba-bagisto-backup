<?php

namespace Fujiba\SupplierFinder\Models;

use Illuminate\Database\Eloquent\Model;

class FabricMatch extends Model
{
    protected $table = 'sf_matches';

    protected $guarded = ['id'];

    protected $casts = ['details' => 'array', 'score' => 'float'];

    public function crawled()
    {
        return $this->belongsTo(CrawledProduct::class, 'crawled_product_id');
    }
}
