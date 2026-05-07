<?php

namespace Webkul\Pos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Pos\Contracts\FixedExpenseApplication as FixedExpenseApplicationContract;

class FixedExpenseApplication extends Model implements FixedExpenseApplicationContract
{
    protected $table = 'pos_fixed_expense_applications';

    protected $fillable = [
        'fixed_expense_id',
        'outlet_id',
        'month',
        'applied_amount',
        'status',
        'note',
        'applied_at',
    ];

    protected $casts = [
        'applied_at'     => 'datetime',
        'applied_amount' => 'decimal:4',
    ];

    public function fixedExpense(): BelongsTo
    {
        return $this->belongsTo(FixedExpenseProxy::modelClass(), 'fixed_expense_id');
    }
}
