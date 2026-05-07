<?php

namespace Webkul\Pos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Webkul\Pos\Contracts\Bank as BankContract;

class Bank extends Model implements BankContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pos_banks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'agent_id',
        'name',
        'address',
        'email',
        'phone',
        'status',
    ];

    /**
     * Get Users associated with the bank.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(UserProxy::modelClass(), 'pos_banks', 'agent_id', 'id');
    }
}
