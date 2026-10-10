<?php

namespace Fujiba\SupplierFinder\Models;

use Illuminate\Database\Eloquent\Model;

class CodeRule extends Model
{
    protected $table = 'sf_code_rules';

    protected $guarded = ['id'];

    protected $casts = ['enabled' => 'bool', 'confidence' => 'float'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
