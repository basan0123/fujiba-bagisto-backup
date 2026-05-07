<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Inventory\Models\InventorySourceProxy;
use Webkul\User\Models\AdminProxy;

class ProductStockAudit extends Model
{
    protected $table = 'product_stock_audits';

    protected $fillable = [
        'product_id',
        'inventory_source_id',
        'qty_before',
        'qty_after',
        'delta',
        'admin_id',
        'source_action',
        'remark',
        'remark_updated_by',
        'remark_updated_at',
    ];

    protected $casts = [
        'remark_updated_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    public function inventorySource(): BelongsTo
    {
        return $this->belongsTo(InventorySourceProxy::modelClass(), 'inventory_source_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'admin_id');
    }

    public function remarkUpdater(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'remark_updated_by');
    }
}
