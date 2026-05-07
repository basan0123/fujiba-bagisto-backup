<?php

namespace Webkul\Pos\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Pos\Contracts\CustomerCredit as CustomerCreditContract;

class CustomerCredit extends Model implements CustomerCreditContract
{
    /**
     * Table associated with the model
     *
     * @var array
     */
    protected $table = 'pos_customer_credit';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_id',
        'customer_id',
        'change_amount',
        'base_change_amount',
        'tendered_amount',
        'base_tendered_amount',
        'payment_mode',
        'used_status',
    ];
}
