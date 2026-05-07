<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class MessengerPageResolver
{
    /**
     * Resolve tenant + channel from a Facebook page ID.
     */
    public function resolveByPageId(string $pageId): ?array
    {
        $row = DB::connection('mysql')
            ->table('messenger_page_channel_map')
            ->where('facebook_page_id', $pageId)
            ->where('is_active', 1)
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'tenant_id'        => $row->tenant_id,
            'tenant_db'        => $row->tenant_db,
            'channel_id'       => $row->channel_id,
            'channel_code'     => $row->channel_code,
            'chatpion_api_key' => $row->chatpion_api_key,
            'chatpion_store_id'=> $row->chatpion_store_id,
        ];
    }

    /**
     * Find a customer in a tenant DB by messenger subscriber ID.
     */
    public function resolveBySubscriberId(string $subscriberId, string $tenantDb): ?array
    {
        $this->switchTenantDb($tenantDb);

        $customer = DB::connection('tenant')
            ->table('customers')
            ->where('token', $subscriberId)
            ->first();

        if (! $customer) {
            return null;
        }

        return (array) $customer;
    }

    /**
     * Get all active channel mappings for a tenant.
     */
    public function getTenantChannels(string $tenantId): array
    {
        return DB::connection('mysql')
            ->table('messenger_page_channel_map')
            ->where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    /**
     * Switch the tenant database connection.
     */
    public function switchTenantDb(string $tenantDb): void
    {
        config(['database.connections.tenant.database' => $tenantDb]);
        DB::purge('tenant');
        DB::reconnect('tenant');
    }
}
