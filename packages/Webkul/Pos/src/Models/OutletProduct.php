<?php

namespace Webkul\Pos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Webkul\Pos\Contracts\OutletProduct as OutletProductContract;
use Webkul\Product\Models\ProductProxy;

class OutletProduct extends Model implements OutletProductContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pos_outlet_product';

    /**
     * The attributes that are mass assignable.
     *
     * @var string
     */
    protected $fillable = [
        'product_id',
        'outlet_id',
        'status',
    ];

    /**
     * Get Outlet that owns the pos product.
     */
    public function outlets(): BelongsToMany
    {
        return $this->belongsToMany(OutletProxy::modelClass(), 'pos_outlet_product', 'product_id', 'id');
    }

    /**
     * Get the product that owns the pos product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    /**
     * The barcode that belong to the pos product.
     */
    public function barcode(): HasOne
    {
        return $this->product->hasOne(ProductBarcodeProxy::modelClass(), 'product_id');
    }
}
