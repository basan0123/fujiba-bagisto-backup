<?php

namespace App\Http\Controllers;

use App\Models\LoginToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Webkul\Customer\Models\Customer;

class MessengerLoginController extends Controller
{
    protected const API_KEY = '1-w56biMy1762254116QMchRgq';
    protected const API_URL = 'https://fujiba.shop/api/subscriber_information/';

    public function login(Request $request)
    {
        $subscriberId = (string) $request->query('subscriber_id', '');
        $target       = (string) $request->query('target', '');

        if ($subscriberId === '' || ! preg_match('/^[A-Za-z0-9_-]+$/', $subscriberId)) {
            Log::warning("MessengerLogin: invalid subscriber_id '{$subscriberId}'");
            return redirect()->route('shop.home.index');
        }

        $channelId = core()->getCurrentChannel()->id;

        $customer = Customer::where('chatpion_subscribe_id', $subscriberId)
            ->where('channel_id', $channelId)
            ->first();

        if (! $customer) {
            $customer = $this->createFromChatpion($subscriberId, $channelId);
        } else {
            $this->backfillIfPlaceholder($customer, $subscriberId);
        }

        if (! $customer) {
            return redirect()->route('customer.session.index')
                ->with('warning', 'Could not link your Messenger account.');
        }

        $this->upsertMessengerToken($customer, $subscriberId, core()->getCurrentChannel()->code ?? 'default');

        [, $rawToken] = LoginToken::issue(
            (int) $customer->id,
            10,
            $request->ip(),
            $request->userAgent()
        );

        // Land on the Blade storefront via the session-mint exchange page.
        $url = route('messenger.exchange', [
            'token'  => $rawToken,
            'target' => $target ?: null,
        ]);

        return redirect()->to($url);
    }

    protected function createFromChatpion(string $subscriberId, int $channelId): ?Customer
    {
        $info = $this->fetchSubscriber($subscriberId);

        $firstName = $info['first_name'] ?? 'Messenger';
        $lastName  = $info['last_name']  ?? 'User';
        // Channel-scoped synthetic email: customers.email is globally unique,
        // so the same subscriber landing on two channels needs distinct rows.
        $email     = ! empty($info['email']) ? $info['email'] : "msg_{$subscriberId}_c{$channelId}@messenger.local";
        $phone     = ! empty($info['phone_number']) ? $info['phone_number'] : null;
        $gender    = strtolower($info['gender'] ?? '') ?: null;
        $image     = $info['profile_pic'] ?? null;

        $existing = Customer::where('email', $email)->where('channel_id', $channelId)->first();
        if ($existing) {
            $existing->chatpion_subscribe_id = $subscriberId;
            $existing->save();
            return $existing;
        }

        // Real email already on another channel: append channel suffix to keep email unique.
        if (! empty($info['email']) && Customer::where('email', $email)->exists()) {
            $email = "c{$channelId}+{$email}";
        }

        return Customer::create([
            'first_name'            => $firstName,
            'last_name'             => $lastName,
            'gender'                => $gender,
            'email'                 => $email,
            'phone'                 => $phone,
            'image'                 => $image,
            'chatpion_subscribe_id' => $subscriberId,
            'password'              => bcrypt(Str::random(32)),
            'status'                => 1,
            'is_verified'           => 1,
            'customer_group_id'     => 2,
            'channel_id'            => $channelId,
        ]);
    }

    protected function backfillIfPlaceholder(Customer $customer, string $subscriberId): void
    {
        $isPlaceholder = ($customer->first_name === 'Customer' || $customer->first_name === null)
            && ($customer->last_name === '-' || $customer->last_name === null);

        if (! $isPlaceholder && ! str_contains((string) $customer->email, '@messenger.local')) {
            return;
        }

        $info = $this->fetchSubscriber($subscriberId);
        if (! $info) return;

        $dirty = false;
        if (! empty($info['first_name']) && $isPlaceholder) { $customer->first_name = $info['first_name']; $dirty = true; }
        if (! empty($info['last_name'])  && $isPlaceholder) { $customer->last_name  = $info['last_name'];  $dirty = true; }
        if (! empty($info['email']) && str_contains((string) $customer->email, '@messenger.local')) {
            $customer->email = $info['email']; $dirty = true;
        }
        if (! empty($info['phone_number']) && empty($customer->phone)) { $customer->phone = $info['phone_number']; $dirty = true; }
        if (! empty($info['profile_pic']) && empty($customer->image)) { $customer->image = $info['profile_pic']; $dirty = true; }

        if ($dirty) $customer->save();
    }

    protected function upsertMessengerToken(Customer $customer, string $subscriberId, string $channelCode): void
    {
        $pageId = config("messenger_channels.{$channelCode}.page_id")
            ?? config('messenger_channels.default.page_id');

        if (! $pageId) {
            return;
        }

        try {
            DB::table('customer_channel_tokens')->updateOrInsert(
                [
                    'customer_id' => $customer->id,
                    'provider'    => 'messenger',
                    'page_id'     => (string) $pageId,
                    'token'       => $subscriberId,
                ],
                [
                    'is_active'   => 1,
                    'verified_at' => now(),
                    'updated_at'  => now(),
                    'created_at'  => now(),
                ],
            );
        } catch (\Throwable $e) {
            Log::warning('MessengerLogin: token upsert failed', [
                'customer_id' => $customer->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    protected function fetchSubscriber(string $subscriberId): ?array
    {
        try {
            $res = Http::timeout(10)->withoutVerifying()->get(self::API_URL, [
                'api_key'       => self::API_KEY,
                'subscriber_id' => $subscriberId,
            ]);
            $data = $res->json();
            if (($data['status'] ?? '') !== 'success') return null;
            return $data['subscriber_info'] ?? null;
        } catch (\Throwable $e) {
            Log::warning("Messenger fetchSubscriber failed: " . $e->getMessage());
            return null;
        }
    }
}
