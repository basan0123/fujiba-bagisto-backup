<?php

namespace Fujiba\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timecard extends Model
{
    protected $table = 'fujiba_timecards';

    protected $guarded = ['id'];

    protected $casts = [
        'work_date'   => 'date',
        'clock_in'    => 'datetime',
        'clock_out'   => 'datetime',
        'break_start' => 'datetime',
        'break_end'   => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}
