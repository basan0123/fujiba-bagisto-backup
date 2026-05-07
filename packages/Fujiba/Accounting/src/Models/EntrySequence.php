<?php

namespace Fujiba\Accounting\Models;

use Illuminate\Database\Eloquent\Model;

class EntrySequence extends Model
{
    protected $table = 'fujiba_entry_sequences';

    protected $primaryKey = 'year_month';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];
}
