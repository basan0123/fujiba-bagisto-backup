<?php

namespace Fujiba\SupplierFinder\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'sf_suppliers';

    protected $guarded = ['id'];

    protected $casts = [];

}
