<?php

namespace Fujiba\AutoLogin\Services;

use Illuminate\Support\Str;
use Webkul\Customer\Models\Customer;

/**
 * Resolves a Bagisto Customer for a TikTok open_id, scoped to a channel.
 *
 * Shared between:
 *   - Fujiba\AutoLogin\Http\Controllers\TikTokLoginController (interactive SSO)
 *   - Fujiba\TikTokShop\Services\TikTokOrderImportService    (webhook order import)
 *
 * Lookup order:
 *   1. tiktok_open_id + channel_id  (the canonical link)
 *   2. synthetic email + channel_id (handles legacy rows or rows where the
 *      open_id link was lost; backfills tiktok_open_id on hit)
 *   3. Create new Customer with full required fields, then set
 *      tiktok_open_id via direct assignment + save (because Customer::$fillable
 *      does not list tiktok_open_id — passing it through Customer::create()
 *      silently drops it — same Bagisto-vendor-model gotcha that affects
 *      chatpion_subscribe_id; see Sprint 1 controller for precedent).
 *
 * Synthetic email format:    tt_{$openId}@tiktok.local
 * Same prefix Sprint 1 uses; the chatpion equivalent in MessengerLoginController
 * is msg_{$subscriberId}@messenger.local.
 *
 * Profile data ($profile) is optional. When supplied (e.g. by interactive SSO
 * after fetching from TikTok's profile API), first_name / last_name / email
 * are taken from it. When absent (e.g. webhook order import where TikTok
 * doesn't return buyer email by default), placeholders are used:
 *   first_name = 'TikTok', last_name = 'User', email = synthetic.
 */
class TikTokCustomerResolver
{
    /**
     * @param  array{first_name?:string,last_name?:string,email?:string}|null  $profile
     */
    public function resolveOrCreate(string $openId, int $channelId, ?array $profile = null): Customer
    {
        // 1. Find by tiktok_open_id + channel
        $customer = Customer::where('tiktok_open_id', $openId)
            ->where('channel_id', $channelId)
            ->first();

        if ($customer) {
            return $customer;
        }

        $syntheticEmail = $this->syntheticEmail($openId);

        // 2. Find by synthetic email + channel (legacy / backfill path)
        $existing = Customer::where('email', $syntheticEmail)
            ->where('channel_id', $channelId)
            ->first();

        if ($existing) {
            // Backfill the open_id link if missing
            if ($existing->tiktok_open_id !== $openId) {
                $existing->tiktok_open_id = $openId;
                $existing->save();
            }
            return $existing;
        }

        // 3. Create with full required Bagisto Customer fields
        $firstName = $profile['first_name'] ?? 'TikTok';
        $lastName  = $profile['last_name']  ?? 'User';
        $email     = ! empty($profile['email']) ? $profile['email'] : $syntheticEmail;

        $customer = Customer::create([
            'first_name'        => $firstName,
            'last_name'         => $lastName,
            'email'             => $email,
            'password'          => bcrypt(Str::random(32)),
            'status'            => 1,
            'is_verified'       => 1,
            'customer_group_id' => 2,
            'channel_id'        => $channelId,
        ]);

        // tiktok_open_id is NOT in Customer::$fillable — direct assign + save.
        $customer->tiktok_open_id = $openId;
        $customer->save();

        return $customer;
    }

    /**
     * The canonical synthetic-email format for TikTok-linked customers.
     * Public so callers building order rows from a webhook payload (which
     * lacks email) can use it for the order's customer_email field when no
     * Customer was created (true guest path).
     */
    public function syntheticEmail(string $openId): string
    {
        return 'tt_' . $openId . '@tiktok.local';
    }
}
