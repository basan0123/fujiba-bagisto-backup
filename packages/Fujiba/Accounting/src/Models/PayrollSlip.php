<?php

namespace Fujiba\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollSlip extends Model
{
    protected $table = 'fujiba_payroll_slips';

    protected $guarded = ['id'];

    protected $casts = [
        'pay_date'             => 'date',
        'finalized_at'         => 'datetime',
        'is_bonus'             => 'boolean',
        'prev_taxable'         => 'decimal:2',
        'calc_meta'            => 'array',
        'base_pay'             => 'decimal:2',
        'overtime_pay'         => 'decimal:2',
        'night_pay'            => 'decimal:2',
        'holiday_pay'          => 'decimal:2',
        'commute_allowance'    => 'decimal:2',
        'other_allowance'      => 'decimal:2',
        'gross_pay'            => 'decimal:2',
        'health_insurance'     => 'decimal:2',
        'pension'              => 'decimal:2',
        'employment_insurance' => 'decimal:2',
        'income_tax'           => 'decimal:2',
        'resident_tax'         => 'decimal:2',
        'other_deduction'      => 'decimal:2',
        'total_deduction'      => 'decimal:2',
        'net_pay'              => 'decimal:2',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    /** Sprint 7 Phase B: original buggy slip → its correction(s). */
    public function corrections(): HasMany
    {
        return $this->hasMany(self::class, 'corrected_from_slip_id');
    }

    /** Sprint 7 Phase B: a correction slip → original. */
    public function correctedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'corrected_from_slip_id');
    }

    public function isCorrection(): bool
    {
        return $this->status === 'correction';
    }
}
