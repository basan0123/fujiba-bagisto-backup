<?php

namespace Fujiba\Notification\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationAdminRecipient extends Model
{
    protected $table = 'notification_admin_recipients';

    protected $fillable = [
        'channel_id',
        'page_id',
        'psid',
        'label',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'bool',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
