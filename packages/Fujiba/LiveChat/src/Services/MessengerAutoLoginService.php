<?php

namespace Fujiba\LiveChat\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Fujiba\LiveChat\Models\MessengerPageMap;
use Fujiba\LiveChat\Models\LiveChatSetting;

class MessengerAutoLoginService
{
    /**
     * Full auto-login flow:
     * 1. Resolve tenant+channel from page_id
     * 2. Connect to tenant DB
     * 3. Find or create customer from subscriber_id
     * 4. Log them in
     */
    public function loginBySubscriberId(string $subscriberId, string $pageId): array
    {
        // Step 1: Resolve mapping
        $map = MessengerPageMap::where('facebook_page_id', $pageId)
            ->where('is_active', true)
            ->first();

        if (!$map) {
            return ['success' => false, 'error' => 'Page not configured for any store'];
        }

        // Step 2: Switch to tenant DB
        $this->switchTenantDatabase($map->tenant_db);

        // Step 3: Get Chatpion setting for this channel
        $setting = LiveChatSetting::where('tenant_id', $map->tenant_id)
            ->where('channel_id', $map->channel_id)
            ->first();

        if (!$setting || !$setting->auto_login_enabled) {
            return ['success' => false, 'error' => 'Auto-login disabled for this channel'];
        }

        // Step 4: Find customer by subscriber_id in tenant DB
        $customer = DB::connection('tenant')
            ->table('customers')
            ->where('messenger_subscriber_id', $subscriberId)
            ->first();

        // Step 5: If not found, fetch from Chatpion and create/link customer
        if (!$customer) {
            $chatpion  = new ChatpionFailoverService($setting);
            $subInfo   = $chatpion->apiGet('subscriber_information', ['subscriber_id' => $subscriberId]);

            if (($subInfo['status'] ?? '') !== 'success') {
                return ['success' => false, 'error' => 'Subscriber not found in Chatpion'];
            }

            $info     = $subInfo['subscriber_info'];
            $email    = $info['email'] ?? null;
            $phone    = $info['phone_number'] ?? null;
            $fullName = $info['full_name'] ?? ($info['first_name'] . ' ' . ($info['last_name'] ?? ''));

            // Try to match existing customer by email or phone
            $existingCustomer = null;
            if ($email) {
                $existingCustomer = DB::connection('tenant')->table('customers')
                    ->where('email', $email)->first();
            }
            if (!$existingCustomer && $phone) {
                $existingCustomer = DB::connection('tenant')->table('customers')
                    ->where('phone', $phone)->first();
            }

            if ($existingCustomer) {
                // Link subscriber_id to existing customer
                DB::connection('tenant')->table('customers')
                    ->where('id', $existingCustomer->id)
                    ->update([
                        'messenger_subscriber_id' => $subscriberId,
                        'messenger_page_id'       => $pageId,
                        'messenger_linked_at'     => now(),
                    ]);
                $customer = DB::connection('tenant')->table('customers')
                    ->where('id', $existingCustomer->id)->first();
            } else {
                // Create new customer
                $nameParts  = explode(' ', trim($fullName), 2);
                $firstName  = $nameParts[0] ?? 'Messenger';
                $lastName   = $nameParts[1] ?? 'User';
                $autoEmail  = $email ?? ($subscriberId . '@messenger.auto');

                $customerId = DB::connection('tenant')->table('customers')->insertGetId([
                    'first_name'               => $firstName,
                    'last_name'                => $lastName,
                    'email'                    => $autoEmail,
                    'phone'                    => $phone,
                    'password'                 => bcrypt(Str::random(32)),
                    'channel_id'               => $map->channel_id,
                    'messenger_subscriber_id'  => $subscriberId,
                    'messenger_page_id'        => $pageId,
                    'messenger_linked_at'      => now(),
                    'status'                   => 1,
                    'is_verified'              => 1,
                    'created_at'               => now(),
                    'updated_at'               => now(),
                ]);

                $customer = DB::connection('tenant')->table('customers')
                    ->where('id', $customerId)->first();
            }
        }

        // Step 6: Resolve Eloquent model on tenant connection.
        // Caller mints the one-time login token and redirects to /pwa.
        try {
            $model = \Webkul\Customer\Models\Customer::on('tenant')->find($customer->id);
            if (! $model) {
                return ['success' => false, 'error' => 'Customer not found in tenant DB'];
            }
        } catch (\Exception $e) {
            Log::error("Messenger auto-login customer resolve failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Customer resolve failed'];
        }

        return [
            'success'      => true,
            'customer'     => $model,
            'channel_code' => $map->channel_code,
            'channel_id'   => $map->channel_id,
            'tenant_id'    => $map->tenant_id,
        ];
    }

    protected function switchTenantDatabase(string $tenantDb): void
    {
        config(['database.connections.tenant' => array_merge(
            config('database.connections.mysql'),
            ['database' => $tenantDb]
        )]);
        DB::purge('tenant');
        DB::reconnect('tenant');
    }
}
