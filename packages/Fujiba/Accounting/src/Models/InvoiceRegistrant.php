<?php

namespace Fujiba\Accounting\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceRegistrant extends Model
{
    protected $table = 'fujiba_invoice_registrants';

    protected $primaryKey = 't_number';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'raw_json'          => 'array',
        'registration_date' => 'date',
        'disposition_date'  => 'date',
        'fetched_at'        => 'datetime',
    ];
}
