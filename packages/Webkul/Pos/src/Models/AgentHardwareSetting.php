<?php

namespace Webkul\Pos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentHardwareSetting extends Model
{
    protected $table = 'pos_agent_hardware_settings';

    protected $fillable = [
        'agent_id',
        'paper_size',
        'default_printer',
        'default_scanner',
        'extra',
    ];

    protected $casts = [
        'extra' => 'array',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
