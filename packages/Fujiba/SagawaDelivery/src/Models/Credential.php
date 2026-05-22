<?php

namespace Fujiba\SagawaDelivery\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Credential extends Model
{
    protected $table = 'sagawa_credentials';

    protected $fillable = [
        'tenant_id', 'channel_id', 'channel_code',
        'api_customer_id', 'api_password',
        'api_endpoint', 'use_sandbox',
        'sender_name', 'sender_postcode', 'sender_prefecture',
        'sender_city', 'sender_address', 'sender_phone',
        'enabled',
        'use_sagawa_rate', 'priority', 'preparation_days',
        'support_phone', 'support_hours', 'support_branch',
        'daibiki_payment_type',
    ];

    protected $casts = [
        'use_sandbox'      => 'bool',
        'enabled'          => 'bool',
        'use_sagawa_rate'  => 'bool',
        'priority'         => 'integer',
        'preparation_days' => 'integer',
    ];

    protected $connection = 'mysql';

    protected function apiCustomerId(): Attribute
    {
        return Attribute::make(
            get: fn ($v) => $v === null ? null : $this->tryDecrypt($v),
            set: fn ($v) => $v === null ? null : Crypt::encryptString((string) $v),
        );
    }

    protected function apiPassword(): Attribute
    {
        return Attribute::make(
            get: fn ($v) => $v === null ? null : $this->tryDecrypt($v),
            set: fn ($v) => $v === null ? null : Crypt::encryptString((string) $v),
        );
    }

    private function tryDecrypt(string $v): string
    {
        try {
            return Crypt::decryptString($v);
        } catch (\Throwable) {
            return $v; // value was stored plaintext (pre-encryption rows)
        }
    }

    public function senderArray(): array
    {
        return array_filter([
            'name'       => $this->sender_name,
            'postcode'   => $this->sender_postcode,
            'prefecture' => $this->sender_prefecture,
            'city'       => $this->sender_city,
            'address'    => $this->sender_address,
            'phone'      => $this->sender_phone,
        ], fn ($v) => $v !== null && $v !== '');
    }
}
