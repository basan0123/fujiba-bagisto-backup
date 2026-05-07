<?php

namespace Fujiba\Accounting\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'fujiba_settings';

    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];
}
