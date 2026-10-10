<?php

namespace Fujiba\SupplierFinder\Models;

use Illuminate\Database\Eloquent\Model;

class CrawledProduct extends Model
{
    protected $table = 'sf_crawled_products';

    protected $guarded = ['id'];

    protected $casts = ['codes' => 'array', 'colorways' => 'array', 'image_urls' => 'array', 'features' => 'array', 'crawled_at' => 'datetime', 'gone_at' => 'datetime'];

    public function shop()
    {
        return $this->belongsTo(SourceShop::class, 'shop_id');
    }

    public function fabric()
    {
        return $this->belongsTo(SourceFabric::class, 'source_fabric_id');
    }

    public function matches()
    {
        return $this->hasMany(FabricMatch::class);
    }
}
