<?php

namespace Webkul\Pos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StocktakeSession extends Model
{
    protected $table = 'pos_stocktake_sessions';

    protected $fillable = [
        'outlet_id',
        'created_by',
        'status',
        'start_date',
        'completed_date',
        'notes',
    ];

    protected $casts = [
        'start_date'     => 'date',
        'completed_date' => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(StocktakeItem::class, 'session_id');
    }
}
