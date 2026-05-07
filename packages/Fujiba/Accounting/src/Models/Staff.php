<?php

namespace Fujiba\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Staff extends Model
{
    protected $table = 'fujiba_staff';

    protected $guarded = ['id'];

    protected $casts = [
        'hire_date'                     => 'date',
        'termination_date'              => 'date',
        'enrolled_health_insurance'     => 'boolean',
        'enrolled_pension'              => 'boolean',
        'enrolled_employment_insurance' => 'boolean',
        'is_active'                     => 'boolean',
        'hourly_rate'                   => 'decimal:2',
        'monthly_salary'                => 'decimal:2',
    ];

    public function timecards(): HasMany
    {
        return $this->hasMany(Timecard::class, 'staff_id');
    }

    public function payrollSlips(): HasMany
    {
        return $this->hasMany(PayrollSlip::class, 'staff_id');
    }
}
