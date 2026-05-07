<?php

namespace Webkul\Pos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Pos\Contracts\CashMovement as CashMovementContract;

class CashMovement extends Model implements CashMovementContract
{
    protected $table = 'pos_cash_movements';

    protected $fillable = [
        'outlet_id',
        'user_id',
        'drawer_id',
        'type',
        'category',
        'description',
        'amount',
        'currency',
        'movement_date',
        'reference_number',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'amount'        => 'decimal:4',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(OutletProxy::modelClass(), 'outlet_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass(), 'user_id');
    }

    public function drawer(): BelongsTo
    {
        return $this->belongsTo(UserDrawerProxy::modelClass(), 'drawer_id');
    }
}
