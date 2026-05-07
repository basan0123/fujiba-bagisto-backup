<?php

namespace Webkul\Pos\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ReceiptSetting extends Model
{
    protected $table = 'pos_receipt_settings';

    protected $fillable = [
        'outlet_id',
        'logo_path',
        'store_name',
        'address_line1',
        'address_line2',
        'phone',
        'url',
        'footer_message',
    ];

    public function logoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null,
        );
    }
}
