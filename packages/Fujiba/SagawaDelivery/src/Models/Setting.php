<?php

namespace Fujiba\SagawaDelivery\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'sagawa_shipping_settings';

    protected $connection = 'mysql';

    protected $fillable = [
        'tenant_id', 'channel_id', 'channel_code',
        'processing_days', 'cutoff_hour', 'cutoff_minute', 'max_days_ahead',
        'zone_days', 'time_slots', 'blackout_dates',
        'weekly_default', 'extra_prep_dates',
        'enabled', 'require_delivery_date',
    ];

    protected $casts = [
        'processing_days'       => 'integer',
        'cutoff_hour'           => 'integer',
        'cutoff_minute'         => 'integer',
        'max_days_ahead'        => 'integer',
        'zone_days'             => 'array',
        'time_slots'            => 'array',
        'blackout_dates'        => 'array',
        'weekly_default'        => 'array',
        'extra_prep_dates'      => 'array',
        'enabled'               => 'bool',
        'require_delivery_date' => 'bool',
    ];
}
