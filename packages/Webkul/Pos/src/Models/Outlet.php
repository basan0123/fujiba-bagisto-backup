<?php

namespace Webkul\Pos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Webkul\Inventory\Models\InventorySourceProxy;
use Webkul\Pos\Contracts\Outlet as OutletContract;

class Outlet extends Model implements OutletContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pos_outlets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'website',
        'customer_care_number',
        'gst_number',
        'low_stock_qty',
        'address',
        'country',
        'state',
        'city',
        'postcode',
        'status',
        'receipt_id',
        'inventory_source_id',
    ];

    /**
     * Get the User that owns the outlet.
     */
    public function pos_user(): HasOne
    {
        return $this->hasOne(UserProxy::modelClass(), 'outlet_id');
    }

    /**
     * Get the drawer associated with the PosOutlet
     */
    public function products(): HasMany
    {
        return $this->hasMany(OutletProductProxy::modelClass(), 'outlet_id');
    }

    /**
     * Get the receipt associated with the PosOutlet
     */
    public function receipt(): HasOne
    {
        return $this->hasOne(ReceiptProxy::modelClass(), 'id', 'receipt_id');
    }

    /**
     * Get the inventory associated with the PosOutlet
     */
    public function inventory_source(): BelongsTo
    {
        return $this->belongsTo(InventorySourceProxy::modelClass());
    }
}
