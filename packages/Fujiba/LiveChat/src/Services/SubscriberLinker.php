<?php

namespace Fujiba\LiveChat\Services;

use Illuminate\Support\Facades\DB;

/**
 * Match a Chatpion subscriber payload against a Bagisto customer record
 * (by email then phone) and link the two by writing messenger_subscriber_id.
 *
 * Designed to be reusable from both:
 *   - the one-time sync command (artisan fujiba:sync-chatpion-subscribers)
 *   - the future inbound webhook receiver
 *
 * Returns a structured result so callers can render dry-run reports
 * without rewriting the matching logic.
 */
class SubscriberLinker
{
    public const RESULT_LINKED        = 'linked';
    public const RESULT_ALREADY_LINKED = 'already_linked';
    public const RESULT_NO_MATCH      = 'no_match';
    public const RESULT_SKIPPED       = 'skipped';

    /**
     * @param  array  $payload  Chatpion subscriber fields:
     *   subscriber_id (or from_user_id), email, phone_number,
     *   first_name, last_name, page_id (optional)
     * @param  int|null  $channelId  Restrict the customer match to one channel
     * @param  bool  $dryRun  When true, no DB writes happen
     */
    public function link(array $payload, ?int $channelId = null, bool $dryRun = false): array
    {
        $subscriberId = (string) ($payload['subscriber_id'] ?? $payload['from_user_id'] ?? '');
        $email        = trim((string) ($payload['email']        ?? ''));
        $phone        = trim((string) ($payload['phone_number'] ?? $payload['phone'] ?? ''));
        $pageId       = (string) ($payload['page_id'] ?? '');

        if ($subscriberId === '') {
            return $this->result(self::RESULT_SKIPPED, null, 'missing subscriber_id', $payload);
        }

        if ($email === '' && $phone === '') {
            return $this->result(self::RESULT_NO_MATCH, null, 'no email or phone in payload', $payload);
        }

        $customer = $this->findCustomer($email, $phone, $channelId);

        if (! $customer) {
            return $this->result(self::RESULT_NO_MATCH, null, 'no customer with that email/phone', $payload);
        }

        if (! empty($customer->messenger_subscriber_id)) {
            return $this->result(self::RESULT_ALREADY_LINKED, $customer, null, $payload);
        }

        if (! $dryRun) {
            DB::table('customers')->where('id', $customer->id)->update([
                'messenger_subscriber_id' => $subscriberId,
                'messenger_page_id'       => $pageId ?: $customer->messenger_page_id,
                'messenger_linked_at'     => now(),
                'updated_at'              => now(),
            ]);
        }

        return $this->result(self::RESULT_LINKED, $customer, null, $payload);
    }

    /**
     * Email match first (more reliable), phone match as fallback.
     * Skips synthetic emails created by autologin (msg_*@messenger.local).
     */
    protected function findCustomer(string $email, string $phone, ?int $channelId)
    {
        $baseQuery = DB::table('customers');

        if ($channelId !== null) {
            $baseQuery->where('channel_id', $channelId);
        }

        if ($email !== '' && ! str_ends_with(strtolower($email), '@messenger.local') && ! str_ends_with(strtolower($email), '@messenger.auto')) {
            $hit = (clone $baseQuery)->where('email', $email)->first();
            if ($hit) {
                return $hit;
            }
        }

        if ($phone !== '') {
            $hit = (clone $baseQuery)->where('phone', $phone)->first();
            if ($hit) {
                return $hit;
            }
        }

        return null;
    }

    protected function result(string $code, $customer, ?string $reason, array $payload): array
    {
        return [
            'result'        => $code,
            'customer_id'   => $customer?->id,
            'customer_name' => $customer ? trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) : null,
            'subscriber_id' => $payload['subscriber_id'] ?? $payload['from_user_id'] ?? null,
            'email'         => $payload['email'] ?? null,
            'phone'         => $payload['phone_number'] ?? $payload['phone'] ?? null,
            'reason'        => $reason,
        ];
    }
}
