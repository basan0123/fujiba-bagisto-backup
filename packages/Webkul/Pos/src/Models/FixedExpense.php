<?php

namespace Webkul\Pos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Pos\Contracts\FixedExpense as FixedExpenseContract;

class FixedExpense extends Model implements FixedExpenseContract
{
    protected $table = 'pos_fixed_expenses';

    protected $fillable = [
        'outlet_id',
        'name',
        'category',
        'amount',
        'currency',
        'billing_day',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'amount'    => 'decimal:4',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(OutletProxy::modelClass(), 'outlet_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(FixedExpenseApplication::class, 'fixed_expense_id');
    }
}
