<?php

namespace Fujiba\SagawaDelivery\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    protected $table = 'sagawa_api_logs';

    protected $connection = 'mysql';

    protected $fillable = [
        'tenant_id',
        'channel_id',
        'endpoint',
        'base_url',
        'sandbox',
        'request_xml',
        'response_body',
        'http_status',
        'result_code',
        'duration_ms',
        'attempt',
        'error',
    ];

    protected $casts = [
        'sandbox'     => 'bool',
        'http_status' => 'int',
        'duration_ms' => 'int',
        'attempt'     => 'int',
    ];

}
