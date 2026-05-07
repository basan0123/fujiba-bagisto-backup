<?php

// app/Models/UserChannelMap.php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Core\Models\Channel;
use Webkul\Inventory\Models\InventorySource;

class UserChannelMap extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql';

    protected $table = 'user_channel_map';

    protected $fillable = [
        'user_id',
        'channel_id',
        'tenant_id',
        'inventory_source_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // SoftDeletes global scope already filters deleted_at; no manual whereNull needed.
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function inventorySource(): BelongsTo
    {
        return $this->belongsTo(InventorySource::class);
    }
}
