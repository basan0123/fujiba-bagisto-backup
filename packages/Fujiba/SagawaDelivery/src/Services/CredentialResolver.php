<?php

namespace Fujiba\SagawaDelivery\Services;

use Fujiba\SagawaDelivery\Models\Credential;

class CredentialResolver
{
    public function forCurrent(): Credential
    {
        $tenantId  = function_exists('tenant') ? tenant('id') : null;
        $channel   = core()->getCurrentChannel();
        $channelId = $channel?->id;

        return $this->for($tenantId, $channelId, $channel?->code);
    }

    public function for(?string $tenantId, ?int $channelId, ?string $channelCode = null): Credential
    {
        $query = Credential::query()->where('enabled', true);

        if ($tenantId)  { $query->where('tenant_id', $tenantId); }
        if ($channelId) { $query->where('channel_id', $channelId); }

        $cred = $query->first();

        if ($cred) {
            return $cred;
        }

        return $this->fromAdminConfig($tenantId, $channelId, $channelCode);
    }

    /**
     * Build an ephemeral Credential from admin config
     * (core_config entries at sales.carriers.sagawa.*). Not persisted.
     */
    protected function fromAdminConfig(?string $tenantId, ?int $channelId, ?string $channelCode): Credential
    {
        $get = fn (string $field) => core()->getConfigData(
            'sales.carriers.sagawa.'.$field,
            $channelCode
        );

        $active = (string) ($get('active') ?? '0');

        if ($active !== '1' && $active !== 'true') {
            throw new \RuntimeException(sprintf(
                'Sagawa carrier is not enabled for tenant=%s channel=%s. '
                .'Configure it at Admin > Configuration > Sales > Shipping Methods > Sagawa.',
                $tenantId ?? 'null',
                $channelCode ?? ($channelId ?? 'null')
            ));
        }

        $customerId = $get('api_customer_id');
        $password   = $get('api_password');

        if (! $customerId || ! $password) {
            throw new \RuntimeException(
                'Sagawa credentials are incomplete. Set api_customer_id and api_password '
                .'under Admin > Configuration > Sales > Shipping Methods > Sagawa.'
            );
        }

        $cred = new Credential();
        $cred->exists              = false;
        $cred->tenant_id           = $tenantId;
        $cred->channel_id          = $channelId;
        $cred->channel_code        = $channelCode;
        $cred->api_customer_id     = $customerId;
        $cred->api_password        = $password;
        $cred->api_endpoint        = $get('api_endpoint');
        $cred->use_sandbox         = in_array((string) $get('use_sandbox'), ['1', 'true'], true);
        $cred->sender_name         = $get('sender_name');
        $cred->sender_postcode     = $get('sender_postcode');
        $cred->sender_prefecture   = $get('sender_prefecture');
        $cred->sender_city         = $get('sender_city');
        $cred->sender_address      = $get('sender_address');
        $cred->sender_phone        = $get('sender_phone');
        $cred->enabled             = true;

        return $cred;
    }
}
