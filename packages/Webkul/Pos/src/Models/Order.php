<?php

namespace Webkul\Pos\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Webkul\Pos\Contracts\Order as OrderContract;
use Webkul\Sales\Models\OrderProxy;

class Order extends Model implements OrderContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pos_order';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get image url for the category image.
     */
    public function barcodeUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->order_barcode_path ? Storage::url($this->order_barcode_path) : null,
        );
    }

    /**
     * Get the order associated with the PosOrder
     */
    public function order(): HasOne
    {
        return $this->hasOne(OrderProxy::modelClass(), 'id', 'order_id');
    }

    /**
     * Get the outlet associated with the PosOrder
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(OutletProxy::modelClass(), 'outlet_id');
    }

    /**
     * Get the customer credit associated with the PosOrder
     */
    public function customerCredit(): HasOne
    {
        return $this->hasOne(CustomerCreditProxy::modelClass(), 'order_id', 'order_id');
    }
}
