<?php

namespace Fujiba\PwaFrontend\Http\Controllers\Shop;

use App\Models\LoginToken;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Webkul\Customer\Models\Customer;

/**
 * GET /pwa/messenger/autologin?subscriber_id=…&target=…
 *
 * PWA-side counterpart of the storefront's existing
 * App\Http\Controllers\MessengerLoginController. Same Chatpion data source
 * and same per-channel customer mapping, but every redirect lands inside
 * the PWA SPA instead of the legacy Blade storefront.
 *
 * The original controller is intentionally not modified — leave Blade
 * Messenger flows where they are.
 */
class MessengerAutoLoginController extends Controller
{
    protected const API_KEY = '1-w56biMy1762254116QMchRgq';
    protected const API_URL = 'https://fujiba.shop/api/subscriber_information/';

    public function __invoke(Request $request)
    {
        $subscriberId = (string) $request->query('subscriber_id', '');
        $target       = (string) $request->query('target', '');

        $channel   = core()->getCurrentChannel();
        $channelId = $channel?->id;

        if ($subscriberId === '' || ! preg_match('/^[A-Za-z0-9_-]+$/', $subscriberId)) {
            // Most common cause: Chatpion bot template URL is
            //   ".../autologin?#subscriber_id"  (a hash fragment, never sent
            // to the server) instead of
            //   ".../autologin?subscriber_id=#SUBSCRIBER_ID_REPLACE#"
            // (a real query param with the placeholder Chatpion will swap).
            Log::warning('PWA MessengerAutoLogin: invalid/missing subscriber_id', [
                'subscriber_id' => $subscriberId,
                'full_url'      => $request->fullUrl(),
                'referer'       => $request->headers->get('referer'),
                'channel'       => $channel?->code,
            ]);
            return redirect()->to($this->pwaUrl());
        }

        if (! $channelId) {
            return redirect()->to($this->pwaUrl());
        }

        $customer = Customer::where('chatpion_subscribe_id', $subscriberId)
            ->where('channel_id', $channelId)
            ->first();

        if (! $customer) {
            $customer = $this->createForChannel($subscriberId, $channelId, $channel?->code);
        } else {
            $this->backfillIfPlaceholder($customer, $subscriberId);
        }

        if (! $customer) {
            return redirect()->to($this->pwaUrl('customer/login-register'))
                ->with('warning', 'Could not link your Messenger account.');
        }

        // One-time login token; PWA exchanges it via POST /api/autologin
        // for a real Sanctum token. Keeps the long-lived token out of the
        // URL bar / referrer / access logs.
        [, $rawToken] = LoginToken::issue(
            (int) $customer->id,
            10,
            $request->ip(),
            $request->userAgent()
        );

        $url = $this->pwaUrl($this->resolveTarget($target));
        $url .= (str_contains($url, '?') ? '&' : '?') . 'token=' . urlencode($rawToken);

        return redirect()->to($url);
    }

    /**
     * Map a chatbot `target` shorthand to a PWA path. Adding new keys here
     * is the only thing needed to deep-link new screens from the bot.
     */
    protected function resolveTarget(string $target): string
    {
        return match ($target) {
            'orders'   => 'customer/account/orders',
            'profile'  => 'customer/account/profile',
            'wishlist' => 'customer/account/wishlist',
            'cart'     => 'checkout/cart',
            default    => '',
        };
    }

    /**
     * Build a URL into the PWA, honouring the request's host so the
     * customer stays on the channel that initiated the autologin.
     */
    protected function pwaUrl(string $path = ''): string
    {
        $base = rtrim(url('/pwa'), '/');
        $path = ltrim($path, '/');
        return $path === '' ? $base . '/' : $base . '/' . $path;
    }

    /**
     * Create a customer for the requested channel. Uses a channel-scoped
     * synthetic email (msg_{subscriber}_{channel}@messenger.local) so the
     * same Messenger subscriber can autologin into kimono AND mmfood
     * without colliding on the customers.email unique index.
     */
    protected function createForChannel(string $subscriberId, int $channelId, ?string $channelCode): ?Customer
    {
        $info = $this->fetchSubscriber($subscriberId);

        $firstName = $info['first_name'] ?? 'Messenger';
        $lastName  = $info['last_name']  ?? 'User';
        $phone     = $info['phone_number'] ?? null;
        $gender    = strtolower($info['gender'] ?? '') ?: null;
        $image     = $info['profile_pic'] ?? null;

        $code      = $channelCode ?: 'default';
        $realEmail = ! empty($info['email']) ? $info['email'] : null;
        $synthetic = "msg_{$subscriberId}_{$code}@messenger.local";
        $email     = $realEmail ?: $synthetic;

        // Reuse an existing record on this channel if it matches either
        // the real email or the synthetic placeholder.
        $existing = Customer::where('channel_id', $channelId)
            ->where(function ($q) use ($email, $synthetic) {
                $q->where('email', $email)->orWhere('email', $synthetic);
            })
            ->first();

        if ($existing) {
            if (! $existing->chatpion_subscribe_id) {
                $existing->chatpion_subscribe_id = $subscriberId;
                $existing->save();
            }
            return $existing;
        }

        // If the real email already belongs to a different channel's
        // customer, fall back to the synthetic placeholder for this one.
        if ($realEmail) {
            $globalExisting = Customer::where('email', $realEmail)->first();
            if ($globalExisting && (int) $globalExisting->channel_id !== $channelId) {
                $email = $synthetic;
            }
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

    /**
     * Fill in name / email / phone / image when the existing record is a
     * placeholder skeleton (e.g. created server-side before the customer
     * ever opened the bot).
     */
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

    /**
     * Pull subscriber details from Chatpion. Returns null on any failure;
     * the caller falls back to placeholders.
     */
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
            Log::warning('PWA MessengerAutoLogin fetchSubscriber failed: ' . $e->getMessage());
            return null;
        }
    }
}
