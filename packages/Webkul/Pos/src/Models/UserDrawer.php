<?php

namespace Webkul\Pos\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Pos\Contracts\UserDrawer as UserDrawerContract;

class UserDrawer extends Model implements UserDrawerContract
{
    /**
     * The table associated with the model.
     *
     * @var array
     */
    protected $table = 'pos_user_drawer';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'outlet_id',
        'base_currency',
        'opening_currency',
        'opening_amount',
        'status',
        'remark',
    ];
}
