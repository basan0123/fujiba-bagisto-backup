<?php

namespace Webkul\Pos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Pos\Contracts\ProductBarcode as ProductBarcodeContract;

class ProductBarcode extends Model implements ProductBarcodeContract
{
    /**
     * The table associated with the model.
     *
     * @var array
     */
    protected $table = 'pos_product_barcode';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'barcode',
        'barcode_type',
        'gtin',
        'scan_type',
        'scan_value',
        'product_id',
    ];

    /**
     * Get Product Barcode associated with the barcode
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(OutletProductProxy::modelClass());
    }
}
