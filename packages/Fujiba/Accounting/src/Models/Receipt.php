<?php

namespace Fujiba\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\User\Models\Admin;

class Receipt extends Model
{
    protected $table = 'fujiba_receipts';

    protected $guarded = ['id'];

    protected $casts = [
        'ocr_raw_json' => 'array',
        'receipt_date' => 'date',
        'confirmed_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'tax_amount'   => 'decimal:2',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'uploaded_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'confirmed_by');
    }
}
