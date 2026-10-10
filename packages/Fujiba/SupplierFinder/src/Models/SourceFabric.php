<?php

namespace Fujiba\SupplierFinder\Models;

use Illuminate\Database\Eloquent\Model;

class SourceFabric extends Model
{
    protected $table = 'sf_source_fabrics';

    protected $guarded = ['id'];

    protected $casts = ['confidence' => 'float', 'match_score' => 'float', 'contacted_at' => 'datetime'];

    public function products()
    {
        return $this->hasMany(CrawledProduct::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function rule()
    {
        return $this->belongsTo(CodeRule::class, 'code_rule_id');
    }
}
