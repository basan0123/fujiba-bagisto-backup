<?php

namespace Fujiba\LiveChat\Console\Commands;

use Carbon\Carbon;
use Fujiba\LiveChat\Models\AbandonedCartDispatchLog;
use Fujiba\LiveChat\Models\LiveChatSetting;
use Fujiba\Notification\Models\CustomerChannelToken;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * fujiba:dispatch-abandoned-cart  (Phase 1 — text-only, 1 reminder, no carousel)
 *
 * Sends a text Messenger reminder to logged-in customers whose cart has been
 * idle for >= abandoned_cart_delay_minutes (per-channel setting).
 *
 * Sibling to fujiba:dispatch-auto-flow. Mirrors its INSERT-before-send
 * idempotency pattern. Phase 1 is intentionally minimal: no carousel, no
 * step 2/3, no admin UI. Phase 2 adds carousel. Phase 3 adds admin UI.
 * Phase 4 adds multi-step.
 *
 * Each enabled channel must have:
 *   - abandoned_cart_enabled = 1
 *   - abandoned_cart_delay_minutes (default 60)
 *   - abandoned_cart_message_en (required for fallback)
 *   - abandoned_cart_message_ja / _my (optional, used when channel locale matches)
 *
 * Filter logic per channel (uses customers.email join NOT needed — cart row
 * has customer_id, customer_channel_tokens links by customer_id):
 *   - cart.is_active = 1            (not converted to order)
 *   - cart.customer_id IS NOT NULL  (not a guest)
 *   - cart.channel_id = setting.channel_id
 *   - cart.items_count > 0
 *   - cart.updated_at < now() - delay
 *   - no row already exists in abandoned_cart_dispatch_log for (tenant, cart_id, step=1)
 *
 * Per-customer guards (executed at dispatch time):
 *   - active customer_channel_tokens row exists → if not, log skipped_no_token
 *   - last subscriber interaction within FB 24h window — derived from
 *     customer's most-recent token verified_at OR cart.updated_at as proxy.
 *     Phase 1 uses cart.updated_at < 24h as the proxy; if that's too loose
 *     we tighten in Phase 2 by querying ChatPion's last_message_time.
 */
class DispatchAbandonedCartCommand extends Command
{
    protected $signature = 'fujiba:dispatch-abandoned-cart
        {--tenant= : Tenant ID (UUID). Required when --channel is provided.}
        {--channel= : Bagisto channel ID within the tenant.}
        {--all : Process every LiveChatSetting with abandoned_cart_enabled=1 across all tenants.}
        {--dry-run : Print candidates and skip token check, send, and log writes.}
        {--limit=100 : Cap candidates per channel per run.}';

    protected $description = 'Dispatch text-only abandoned-cart reminders to logged-in Messenger customers (Phase 1).';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $limit    = max(1, (int) $this->option('limit'));

        $settings = $this->resolveTargets();
        if ($settings->isEmpty()) {
            $this->info('No matching channels found.');
            return self::SUCCESS;
        }

        $totals = ['candidates' => 0, 'sent' => 0, 'skipped_dup' => 0, 'skipped_no_token' => 0, 'skipped_outside_24h' => 0, 'failed' => 0];

        foreach ($settings as $setting) {
            $perChannel = $this->processChannel($setting, $isDryRun, $limit);
            foreach ($perChannel as $k => $v) {
                $totals[$k] += $v;
            }
        }

        $this->line('');
        $this->info(sprintf(
            'Done. candidates=%d sent=%d skipped_dup=%d skipped_no_token=%d skipped_outside_24h=%d failed=%d%s',
            $totals['candidates'], $totals['sent'], $totals['skipped_dup'],
            $totals['skipped_no_token'], $totals['skipped_outside_24h'], $totals['failed'],
            $isDryRun ? ' [DRY RUN]' : ''
        ));

        return self::SUCCESS;
    }

    protected function resolveTargets()
    {
        if ($this->option('all')) {
            return LiveChatSetting::where('abandoned_cart_enabled', true)->get();
        }

        $tenant  = $this->option('tenant');
        $channel = $this->option('channel');

        if ($channel && ! $tenant) {
            $this->error('--tenant is required when --channel is specified.');
            return collect();
        }
        if (! $channel) {
            $this->error('Provide --all, or both --tenant and --channel.');
            return collect();
        }

        return LiveChatSetting::where('tenant_id', $tenant)
            ->where('channel_id', (int) $channel)
            ->get();
    }

    /**
     * Process one channel: switch to its tenant DB, find candidates, dispatch.
     *
     * @return array<string,int>
     */
    protected function processChannel(LiveChatSetting $setting, bool $isDryRun, int $limit): array
    {
        $totals = ['candidates' => 0, 'sent' => 0, 'skipped_dup' => 0, 'skipped_no_token' => 0, 'skipped_outside_24h' => 0, 'failed' => 0];
        $tag = "tenant={$setting->tenant_id} channel={$setting->channel_id}({$setting->channel_code})";

        if (! $setting->abandoned_cart_enabled) {
            $this->line("[$tag] skipped: abandoned_cart_enabled=false");
            return $totals;
        }

        if (empty($setting->abandoned_cart_message_en)) {
            $this->warn("[$tag] skipped: no English message configured (en is required as fallback)");
            return $totals;
        }

        $delay = $setting->abandoned_cart_delay_minutes ?: 60;
        if ($delay < 5) {
            $this->warn("[$tag] delay ({$delay}min) too short; min 5 minutes for safety. Skipping.");
            return $totals;
        }

        // Switch to the tenant DB to read carts.
        $tenant = \App\Models\Tenant::find($setting->tenant_id);
        if (! $tenant) {
            $this->error("[$tag] tenant model not found");
            return $totals;
        }

        tenancy()->initialize($tenant);
        try {
            $candidateIds = $this->findCandidateCartIds($setting, $delay, $limit);
            $totals['candidates'] = count($candidateIds);
            $this->line("[$tag] delay={$delay}min candidates={$totals['candidates']}");

            if ($isDryRun) {
                foreach ($candidateIds as $cartId) {
                    $cart = DB::table('cart')->where('id', $cartId)->first(['id','customer_id','items_count','grand_total','updated_at']);
                    $this->line(sprintf(
                        '  [dry-run] cart=%d cust=%d items=%d total=%s updated=%s',
                        $cart->id, $cart->customer_id, $cart->items_count, $cart->grand_total, $cart->updated_at
                    ));
                }
                return $totals;
            }

            foreach ($candidateIds as $cartId) {
                $result = $this->dispatchOne($setting, $cartId);
                $totals[$result]++;
            }
        } finally {
            tenancy()->end();
        }

        return $totals;
    }

    /**
     * Find cart_ids that need reminder #1.
     *
     * @return int[]
     */
    protected function findCandidateCartIds(LiveChatSetting $setting, int $delayMinutes, int $limit): array
    {
        $cutoff = Carbon::now()->subMinutes($delayMinutes);

        // Carts already reminded (any step) — the unique key is (tenant, cart, step),
        // so we exclude cart_ids that already have a step=1 row in central DB.
        $alreadyReminded = AbandonedCartDispatchLog::where('tenant_id', $setting->tenant_id)
            ->where('step', 1)
            ->pluck('cart_id')
            ->all();

        $query = DB::connection('tenant')
            ->table('cart')
            ->where('is_active', 1)
            ->whereNotNull('customer_id')
            ->where('channel_id', $setting->channel_id)
            ->where('items_count', '>', 0)
            ->where('updated_at', '<', $cutoff)
            // Phase 1 24h-window proxy: only consider carts updated within last 24h.
            // Carts older than 24h are outside the FB Messenger window and unreachable.
            ->where('updated_at', '>=', Carbon::now()->subHours(24));

        if (! empty($alreadyReminded)) {
            $query->whereNotIn('id', $alreadyReminded);
        }

        return $query->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Dispatch reminder for one cart. Returns the totals key to increment.
     */
    protected function dispatchOne(LiveChatSetting $setting, int $cartId): string
    {
        $cart = DB::connection('tenant')->table('cart')
            ->where('id', $cartId)
            ->first(['id', 'customer_id', 'items_count', 'grand_total']);
        if (! $cart) {
            return 'failed';
        }

        // Pull customer for {{ customer.first_name }} etc.
        $customer = DB::connection('tenant')->table('customers')
            ->where('id', $cart->customer_id)
            ->first(['id', 'first_name', 'last_name', 'channel_id']);

        // Token lookup — central pattern.
        $token = CustomerChannelToken::query()
            ->where('customer_id', $cart->customer_id)
            ->where('provider', CustomerChannelToken::PROVIDER_MESSENGER)
            ->where('is_active', true)
            ->orderByDesc('verified_at')
            ->first();

        // INSERT-before-send: any failure path still leaves a trace for review.
        try {
            $logRow = AbandonedCartDispatchLog::create([
                'tenant_id'         => $setting->tenant_id,
                'channel_id'        => $setting->channel_id,
                'fb_bot_id'         => $setting->fb_bot_id,
                'cart_id'           => $cart->id,
                'customer_id'       => $cart->customer_id,
                'subscriber_id'     => $token?->token ?? '',
                'step'              => 1,
                'candidate_seen_at' => Carbon::now(),
                'status'            => 'pending',
            ]);
        } catch (QueryException $e) {
            if ($this->isDuplicateKey($e)) {
                return 'skipped_dup';
            }
            throw $e;
        }

        if (! $token) {
            $logRow->update([
                'status'        => 'skipped_no_token',
                'dispatched_at' => Carbon::now(),
                'error_message' => 'no active customer_channel_tokens row',
            ]);
            return 'skipped_no_token';
        }

        try {
            $body = $this->resolveMessage($setting, $customer);

            // Phase 2: send text message FIRST, then product carousel.
            // Two-message send keeps body readable even if carousel cards
            // truncate names. If text fails, abort (don't send orphan carousel).
            $sent = $this->sendGraphTextMessage($setting, $token->token, $body);

            if ($sent === true) {
                // Phase 2: build + send product carousel from cart items.
                // Carousel failure is non-fatal — the customer already got the
                // text reminder. Log warning but mark dispatch as sent.
                $cards = $this->buildCarouselFromCart($setting, $cart->id);
                if (! empty($cards)) {
                    $carouselResult = $this->sendGraphCarousel($setting, $token->token, $cards);
                    if ($carouselResult !== true) {
                        Log::warning('[AbandonedCart] Carousel send failed but text succeeded', [
                            'cart_id' => $cart->id,
                            'error' => is_string($carouselResult) ? $carouselResult : 'unknown',
                        ]);
                    }
                }

                $logRow->update([
                    'status'        => 'sent',
                    'dispatched_at' => Carbon::now(),
                ]);
                Log::info('[AbandonedCart] Reminder sent', [
                    'tenant_id' => $setting->tenant_id,
                    'channel_id' => $setting->channel_id,
                    'cart_id' => $cart->id,
                    'customer_id' => $cart->customer_id,
                    'psid' => $token->token,
                    'carousel_cards' => count($cards),
                ]);
                return 'sent';
            }

            $logRow->update([
                'status'        => 'failed',
                'dispatched_at' => Carbon::now(),
                'error_message' => is_string($sent) ? substr($sent, 0, 500) : 'unknown',
            ]);
            return 'failed';
        } catch (\Throwable $e) {
            Log::error('[AbandonedCart] dispatchOne exception', [
                'cart_id' => $cart->id,
                'exception' => $e->getMessage(),
            ]);
            $logRow->update([
                'status'        => 'failed',
                'dispatched_at' => Carbon::now(),
                'error_message' => 'exception: ' . substr($e->getMessage(), 0, 500),
            ]);
            return 'failed';
        }
    }

    /**
     * Pick the right language string. Channel default_locale → ja for kimono,
     * my for mmfood (per locale matrix). Phase 1 uses simple channel-level
     * locale; Phase 4 can switch to per-customer locale if needed.
     */
    protected function resolveMessage(LiveChatSetting $setting, ?object $customer): string
    {
        $code = DB::connection('tenant')->table('channels as c')
            ->leftJoin('locales as l', 'l.id', '=', 'c.default_locale_id')
            ->where('c.id', $setting->channel_id)
            ->value('l.code') ?? 'en';

        $lang = strtolower(substr($code, 0, 2));

        $body = match ($lang) {
            'ja'    => $setting->abandoned_cart_message_ja ?: $setting->abandoned_cart_message_en,
            'my'    => $setting->abandoned_cart_message_my ?: $setting->abandoned_cart_message_en,
            default => $setting->abandoned_cart_message_en,
        };

        // Simple placeholder substitution (same syntax as fujiba_notification).
        // Available placeholders:
        //   {{ customer.first_name }}, {{ customer.last_name }}
        //   {{ cart_url }}  — channel-scoped /checkout/cart link
        $cartUrl = $this->cartUrlForChannel($setting);
        $vars = [
            'customer.first_name' => $customer->first_name ?? '',
            'customer.last_name'  => $customer->last_name ?? '',
            'cart_url'            => $cartUrl,
        ];
        foreach ($vars as $key => $val) {
            $body = preg_replace('/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/', (string) $val, $body);
        }

        // If the admin's body text didn't include {{ cart_url }}, append the URL
        // on its own line so customers always have a tap target above the carousel.
        if ($cartUrl && ! str_contains($body, $cartUrl)) {
            $body .= "\n\n" . $cartUrl;
        }

        return $body;
    }

    /**
     * Send a plain text message via FB Graph API.
     * Reuses the same MESSAGE_TAG pattern as BotFlowSender. We use HUMAN_AGENT
     * because abandoned-cart is conversation continuation initiated by a customer
     * action (cart add) rather than a transactional update.
     *
     * Returns true on success, error message string on failure.
     */
    protected function sendGraphTextMessage(LiveChatSetting $setting, string $psid, string $body)
    {
        $pageInfo = DB::connection('chatpion_import')
            ->table('facebook_rx_fb_page_info')
            ->where('id', $setting->fb_bot_id)
            ->first(['page_id', 'page_access_token']);
        if (! $pageInfo || ! $pageInfo->page_access_token) {
            return 'no page access token for fb_bot_id=' . $setting->fb_bot_id;
        }

        $payload = [
            'recipient'      => ['id' => $psid],
            'messaging_type' => 'MESSAGE_TAG',
            'tag'            => 'HUMAN_AGENT',
            'message'        => ['text' => mb_substr($body, 0, 2000)],
        ];

        $res = Http::timeout(15)->post(
            "https://graph.facebook.com/v18.0/me/messages?access_token={$pageInfo->page_access_token}",
            $payload
        );

        if (! $res->successful()) {
            return 'graph error: ' . substr($res->body(), 0, 400);
        }

        // Mirror into ChatPion's livechat history so the message shows in /admin/fujiba-livechat.
        try {
            $svc = new \Fujiba\LiveChat\Services\ChatpionFailoverService($setting);
            $svc->apiPost('livechat_log_message', [
                'subscriber_id'   => $psid,
                'whatsapp_bot_id' => $setting->fb_bot_id,
                'message_content' => json_encode(['recipient' => ['id' => $psid], 'message' => $payload['message']]),
                'message'         => mb_substr($body, 0, 500),
                'message_type'    => 'text',
                'direction'       => 'out',
                'sender'          => 'bot',
                'agent_name'      => 'AbandonedCart:dispatcher',
                'fb_message_id'   => $res->json('message_id') ?: '',
            ]);
        } catch (\Throwable $e) {
            // Logging mirror failure is non-fatal — the customer still received the message.
            Log::warning('[AbandonedCart] livechat_log_message mirror failed', [
                'psid' => $psid,
                'error' => $e->getMessage(),
            ]);
        }

        return true;
    }

    protected function isDuplicateKey(QueryException $e): bool
    {
        return (int) ($e->errorInfo[1] ?? 0) === 1062;
    }

    /**
     * Build up to 3 carousel cards from cart items.
     * Each card: product image (medium), name, price + qty subtitle, "View Now" web_url button.
     * URLs are channel-scoped (kimono.fujiba.co.jp vs mmfood.fujiba.co.jp).
     *
     * Returns [] if no cards could be built (no items, no images, etc.) — caller skips carousel.
     */
    protected function buildCarouselFromCart(LiveChatSetting $setting, int $cartId): array
    {
        $hostname = DB::connection('tenant')->table('channels')
            ->where('id', $setting->channel_id)
            ->value('hostname');
        if (! $hostname) {
            return [];
        }

        $items = DB::connection('tenant')->table('cart_items')
            ->where('cart_id', $cartId)
            ->orderBy('id', 'desc')
            ->limit(3)
            ->get(['id', 'product_id', 'name', 'quantity', 'price']);

        // Cart-page URL — same on every card. Bagisto's storefront product URLs
        // currently 404 (separate storefront-side bug, see project notes), so
        // every "View Cart" button points to the working /checkout/cart route
        // which actually loads. Customer can resume the entire cart in one tap.
        $cartUrl = "https://{$hostname}/checkout/cart";

        $cards = [];
        foreach ($items as $item) {
            $imgPath = DB::connection('tenant')->table('product_images')
                ->where('product_id', $item->product_id)
                ->orderBy('position', 'asc')
                ->value('path');

            $imageUrl = $imgPath
                ? "https://{$hostname}/storage/" . ltrim($imgPath, '/')
                : "https://{$hostname}/themes/default/assets/images/placeholder.png";

            // Subtitle: price formatted with channel currency; FB caps subtitle at 80 chars
            $price = (float) $item->price;
            $qty   = (int) $item->quantity;
            $subtitle = $this->formatCardSubtitle($setting, $price, $qty);

            $cards[] = [
                'title'     => mb_substr((string) $item->name, 0, 80),
                'image_url' => $imageUrl,
                'subtitle'  => mb_substr($subtitle, 0, 80),
                'buttons'   => [[
                    'type'  => 'web_url',
                    'url'   => $cartUrl,
                    'title' => $this->buttonLabel($setting),
                ]],
            ];
        }

        return $cards;
    }

    /**
     * Build the cart URL for a given channel — used in both the carousel buttons
     * and the top-of-message text link so customer has two paths to the cart.
     */
    protected function cartUrlForChannel(LiveChatSetting $setting): string
    {
        $hostname = DB::connection('tenant')->table('channels')
            ->where('id', $setting->channel_id)
            ->value('hostname');
        return $hostname ? "https://{$hostname}/checkout/cart" : '';
    }

    /**
     * Format the subtitle line. Channel-locale aware:
     *   ja → "¥1,700 × 2"
     *   en/my → "1,700 MMK × 2" or "$17.00 × 2"
     * Phase 2 keeps it simple — channel currency lookup + locale formatting.
     */
    protected function formatCardSubtitle(LiveChatSetting $setting, float $price, int $qty): string
    {
        // Currency from channel: use core() helper if available; otherwise simple JPY/MMK heuristic
        try {
            $currencyCode = DB::connection('tenant')->table('channels as c')
                ->leftJoin('currencies as cur', 'cur.id', '=', 'c.base_currency_id')
                ->where('c.id', $setting->channel_id)
                ->value('cur.code') ?: 'JPY';
        } catch (\Throwable $e) {
            $currencyCode = 'JPY';
        }

        $symbol = match ($currencyCode) {
            'JPY' => '¥',
            'USD' => '$',
            default => '',
        };

        $priceFormatted = $symbol !== ''
            ? $symbol . number_format($price, 0)
            : number_format($price, 0) . ' ' . $currencyCode;

        return $qty > 1 ? "{$priceFormatted} × {$qty}" : $priceFormatted;
    }

    /**
     * Button label per channel locale. Says "View Cart" because every card's
     * button now points to /checkout/cart (see buildCarouselFromCart for why).
     */
    protected function buttonLabel(LiveChatSetting $setting): string
    {
        $code = DB::connection('tenant')->table('channels as c')
            ->leftJoin('locales as l', 'l.id', '=', 'c.default_locale_id')
            ->where('c.id', $setting->channel_id)
            ->value('l.code') ?? 'en';

        return match (strtolower(substr($code, 0, 2))) {
            'ja'    => 'カートを見る',
            'my'    => 'View Cart',
            default => 'View Cart',
        };
    }

    /**
     * Send a Facebook Messenger generic-template carousel.
     * Each cart_item produces one card (image, title, subtitle, button).
     *
     * Uses MESSAGE_TAG/HUMAN_AGENT — same delivery profile as the text part.
     * Returns true on success, error string on failure.
     */
    protected function sendGraphCarousel(LiveChatSetting $setting, string $psid, array $cards)
    {
        if (empty($cards)) {
            return true;
        }

        $pageInfo = DB::connection('chatpion_import')
            ->table('facebook_rx_fb_page_info')
            ->where('id', $setting->fb_bot_id)
            ->first(['page_id', 'page_access_token']);
        if (! $pageInfo || ! $pageInfo->page_access_token) {
            return 'no page access token for fb_bot_id=' . $setting->fb_bot_id;
        }

        $payload = [
            'recipient'      => ['id' => $psid],
            'messaging_type' => 'MESSAGE_TAG',
            'tag'            => 'HUMAN_AGENT',
            'message'        => [
                'attachment' => [
                    'type'    => 'template',
                    'payload' => [
                        'template_type' => 'generic',
                        'elements'      => array_slice($cards, 0, 10), // FB hard cap 10
                    ],
                ],
            ],
        ];

        $res = Http::timeout(20)->post(
            "https://graph.facebook.com/v18.0/me/messages?access_token={$pageInfo->page_access_token}",
            $payload
        );

        if (! $res->successful()) {
            return 'graph carousel error: ' . substr($res->body(), 0, 400);
        }

        // Mirror to ChatPion livechat history so the carousel shows in /admin/fujiba-livechat.
        try {
            $svc = new \Fujiba\LiveChat\Services\ChatpionFailoverService($setting);
            $svc->apiPost('livechat_log_message', [
                'subscriber_id'   => $psid,
                'whatsapp_bot_id' => $setting->fb_bot_id,
                'message_content' => json_encode(['recipient' => ['id' => $psid], 'message' => $payload['message']]),
                'message'         => '[carousel: ' . count($cards) . ' product(s)]',
                'message_type'    => 'template',
                'direction'       => 'out',
                'sender'          => 'bot',
                'agent_name'      => 'AbandonedCart:dispatcher',
                'fb_message_id'   => $res->json('message_id') ?: '',
            ]);
        } catch (\Throwable $e) {
            Log::warning('[AbandonedCart] carousel livechat_log_message mirror failed', [
                'psid' => $psid,
                'error' => $e->getMessage(),
            ]);
        }

        return true;
    }
}
