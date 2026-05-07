<?php

namespace Fujiba\PwaFrontend\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class ConfigController extends Controller
{
    /**
     * GET /api/v1/pwa-frontend/config
     *
     * Returns ALL mobile-storefront frontend config in a single payload.
     * Channel-scoped (resolved from current host) and locale-scoped.
     * Disabled payment methods are filtered out so they never reach the client.
     */
    public function index(): HttpResponse
    {
        $channel     = core()->getCurrentChannel();
        $channelCode = $channel?->code;
        $locale      = app()->getLocale();

        $cfg = fn (string $key) => core()->getConfigData($key, $channelCode, $locale);

        // Brand & layout tokens (per-channel, per-tenant). Empty hex fields
        // fall back to defaults in the runtime CSS so admin can leave them
        // blank when the brand colour is enough on its own.
        $brand = [
            'color'       => $cfg('general.design.brand_color.value')       ?: '#1e1b4b',
            'accent'      => $cfg('general.design.brand_color.accent_color') ?: '#f5a623',
            'dark'        => $cfg('general.design.brand_color.dark_color')  ?: '#1a0800',
            'btn_radius'  => $cfg('general.design.brand_color.btn_radius')  ?: '12px',
            'card_radius' => $cfg('general.design.brand_color.card_radius') ?: '18px',

            'header_bg'   => (string) ($cfg('general.design.brand_color.header_bg')   ?? ''),
            'header_text' => (string) ($cfg('general.design.brand_color.header_text') ?? ''),
            'footer_bg'   => (string) ($cfg('general.design.brand_color.footer_bg')   ?? ''),
            'footer_text' => (string) ($cfg('general.design.brand_color.footer_text') ?? ''),
            'button_text' => $cfg('general.design.brand_color.button_text') ?: '#ffffff',

            'logo'        => $this->imageUrl(
                $cfg('general.design.brand_color.logo_secondary'),
                $channel?->logo_url
            ),
        ];

        // Topbar
        $topbar = [
            'text'                  => (string) ($cfg('general.design.topbar.text') ?? ''),
            'free_ship_min'         => (int) ($cfg('general.design.topbar.free_ship_min') ?? 0),
            'show_lang_switch'      => $this->bool($cfg('general.design.topbar.show_lang_switch'), true),
            'show_currency_switch'  => $this->bool($cfg('general.design.topbar.show_currency_switch'), true),
            'show_theme_toggle'     => $this->bool($cfg('general.design.topbar.show_theme_toggle'), true),
        ];

        // Hero (3 slots, drop empty ones)
        $hero = [];
        for ($i = 1; $i <= 3; $i++) {
            $img = $cfg("general.design.hero.slot_{$i}_image");
            if (! $img) {
                continue;
            }
            $hero[] = [
                'image'    => $this->imageUrl($img),
                'title'    => (string) ($cfg("general.design.hero.slot_{$i}_title") ?? ''),
                'subtitle' => (string) ($cfg("general.design.hero.slot_{$i}_subtitle") ?? ''),
                'badge'    => (string) ($cfg("general.design.hero.slot_{$i}_badge") ?? ''),
                'cta_text' => (string) ($cfg("general.design.hero.slot_{$i}_cta_text") ?? ''),
                'cta_url'  => (string) ($cfg("general.design.hero.slot_{$i}_cta_url") ?? ''),
            ];
        }

        // Footer social
        $social = array_filter([
            'facebook'  => $cfg('general.design.footer_social.facebook_url'),
            'instagram' => $cfg('general.design.footer_social.instagram_url'),
            'messenger' => $cfg('general.design.footer_social.messenger_url'),
            'tiktok'    => $cfg('general.design.footer_social.tiktok_url'),
        ]);

        // Chat
        $chat = [
            'enabled'              => $this->bool($cfg('pwa.chat.enabled')),
            'welcome_message'      => (string) ($cfg('pwa.chat.welcome_message') ?? ''),
            'select_date_message'  => (string) ($cfg('pwa.chat.select_date_message') ?? ''),
            'select_time_message'  => (string) ($cfg('pwa.chat.select_time_message') ?? ''),
            'confirm_message'      => (string) ($cfg('pwa.chat.confirm_message') ?? ''),
            'address_done_message' => (string) ($cfg('pwa.chat.address_done_message') ?? ''),
            'payment_done_message' => (string) ($cfg('pwa.chat.payment_done_message') ?? ''),
        ];

        // Payments — disabled methods are filtered so client never sees them
        $payments = array_values(array_filter([
            $this->bool($cfg('pwa.payments.cod_enabled'), true)
                ? ['code' => 'cashondelivery', 'enabled' => true] : null,
            $this->bool($cfg('pwa.payments.transfer_enabled'), true)
                ? ['code' => 'moneytransfer',  'enabled' => true] : null,
            $this->bool($cfg('pwa.payments.card_enabled'))
                ? ['code' => 'card',           'enabled' => true] : null,
            $this->bool($cfg('pwa.payments.paypay_enabled'))
                ? ['code' => 'paypay',         'enabled' => true] : null,
            $this->bool($cfg('pwa.payments.komoju_enabled'))
                ? ['code' => 'komoju',         'enabled' => true] : null,
        ]));

        // Pickup
        $pickup = [
            'enabled' => $this->bool($cfg('pwa.pickup.enabled')),
            'address' => (string) ($cfg('pwa.pickup.address') ?? ''),
            'hours'   => (string) ($cfg('pwa.pickup.hours') ?? ''),
            'phone'   => (string) ($cfg('pwa.pickup.phone') ?? ''),
        ];

        // Messenger sign-in
        $messenger = [
            'enabled'      => $this->bool($cfg('pwa.messenger.enabled')),
            'page_id'      => (string) ($cfg('pwa.messenger.page_id') ?? ''),
            'mme_url'      => (string) ($cfg('pwa.messenger.mme_url') ?? ''),
            'button_label' => (string) ($cfg('pwa.messenger.login_button_label') ?? ''),
        ];

        // Catalog filters + sort (drives All Products grid)
        $rawCodes = (string) ($cfg('pwa.catalog.filter_attributes') ?? '');
        $filterAttrs = array_values(array_filter(array_map(
            fn ($s) => trim($s),
            preg_split('/[,\s]+/u', $rawCodes) ?: []
        )));

        $catalog = [
            'default_sort'      => (string) ($cfg('pwa.catalog.default_sort') ?? 'created_at-desc'),
            'filter_attributes' => $filterAttrs, // empty = show all filterable
        ];

        // Address requirements — combines Bagisto core toggles
        // (customer.address.requirements.*) with our extra toggles
        // (customer.address.extra_requirements.*). Front-end forms (chat,
        // onepage) read this to decide which inputs are required vs optional.
        $streetLines = (int) ($cfg('customer.address.information.street_lines') ?? 2);
        if ($streetLines < 1) {
            $streetLines = 1;
        } elseif ($streetLines > 4) {
            $streetLines = 4;
        }

        $address = [
            'street_lines'      => $streetLines,
            'postcode_required' => $this->bool($cfg('customer.address.requirements.postcode'), true),
            'state_required'    => $this->bool($cfg('customer.address.requirements.state_required'), true),
            'phone_required'    => $this->bool($cfg('customer.address.extra_requirements.phone'), true),
            'company_required'  => $this->bool($cfg('customer.address.extra_requirements.company_name'), false),
            'billing_required'  => $this->bool($cfg('customer.address.extra_requirements.billing_required'), false),
        ];

        // Cache headers: this payload only changes when admin edits config,
        // so the browser can keep it for ~60 s. With `must-revalidate` the
        // CDN/proxy still re-checks; the gain is for the SPA's repeated
        // fetches across rapid in-app navigation. ETag enables 304 reuse
        // when admin hasn't changed anything.
        $payload = [
            'data' => [
                'channel'   => ['code' => $channelCode, 'name' => $channel?->name],
                'locale'    => $locale,
                'brand'     => $brand,
                'topbar'    => $topbar,
                'hero'      => $hero,
                'social'    => $social,
                'chat'      => $chat,
                'payments'  => $payments,
                'pickup'    => $pickup,
                'messenger' => $messenger,
                'catalog'   => $catalog,
                'address'   => $address,
            ],
        ];

        $etag = '"' . substr(sha1(json_encode($payload['data'])), 0, 16) . '"';

        if (request()->headers->get('If-None-Match') === $etag) {
            return response('', 304)->header('ETag', $etag);
        }

        return response()->json($payload)
            ->header('Cache-Control', 'private, max-age=60, must-revalidate')
            ->header('ETag', $etag)
            ->header('Vary', 'Cookie');
    }

    /**
     * Resolve a stored image path to a public URL using the tenant disk.
     * Falls back to $fallback when the path is empty.
     */
    private function imageUrl(?string $path, ?string $fallback = null): ?string
    {
        if (! $path) {
            return $fallback;
        }

        try {
            return Storage::url($path);
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private function bool(mixed $value, bool $default = false): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }
        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }
}
