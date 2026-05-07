<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessengerHelper
{
    protected function channelCode(): string
    {
        try {
            return core()->getCurrentChannel()->code ?? 'default';
        } catch (\Throwable $e) {
            return 'default';
        }
    }

    public function getContactUrl(): string
    {
        $code   = $this->channelCode();
        $config = config("messenger_channels.{$code}", config('messenger_channels.default'));

        $pageId = $config['page_id'];
        $ref    = $config['ref'] ?? '';
        $query  = $ref ? '?ref=' . $ref : '';

        return "https://m.me/{$pageId}{$query}";
    }

    public function findCustomerSubscriberId(string $email): ?string
    {
        try {
            $row = DB::table('customers')
                ->where('email', $email)
                ->first(['messenger_subscriber_id', 'chatpion_subscribe_id']);

            if (! $row) {
                return null;
            }

            return $row->messenger_subscriber_id
                ?? $row->chatpion_subscribe_id
                ?? null;
        } catch (\Throwable $e) {
            Log::warning('MessengerHelper: subscriber lookup failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function getAutologinUrl(string $subscriberId): string
    {
        $host = request()->getHost();

        return "https://{$host}/messenger/autologin?subscriber_id={$subscriberId}";
    }
}
