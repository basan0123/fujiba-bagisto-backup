<?php

namespace Fujiba\Accounting\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    protected $table = 'fujiba_tax_rates';

    protected $guarded = ['id'];

    protected $casts = [
        'brackets'       => 'array',
        'rate'           => 'decimal:4',
        'effective_from' => 'date',
        'effective_to'   => 'date',
    ];
}
