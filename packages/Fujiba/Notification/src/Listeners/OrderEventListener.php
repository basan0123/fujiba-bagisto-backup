<?php

namespace Fujiba\Notification\Listeners;

use Fujiba\Notification\Jobs\SendMessengerNotificationJob;
use Fujiba\Notification\Models\CustomerChannelToken;
use Fujiba\Notification\Models\NotificationAdminRecipient;
use Fujiba\Notification\Models\NotificationLog;
use Fujiba\Notification\Models\NotificationTemplate;
use Fujiba\Notification\Services\ChannelManager;
use Fujiba\Notification\Services\OrderNotificationMatrix;
use Fujiba\Notification\Services\TemplateRenderer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\Refund;
use Webkul\Sales\Models\Shipment;

/**
 * Single handler for all order-lifecycle events. Registered in the service
 * provider via a closure that captures the Bagisto event name and forwards
 * it here so we can resolve the right template + payload-specific variables.
 */
class OrderEventListener
{
    /**
     * Bagisto events we subscribe to. What each one MEANS (and its
     * duplicate-protection identity) lives in OrderNotificationMatrix.
     */
    public const EVENT_MAP = OrderNotificationMatrix::EVENTS;

    /**
     * Order statuses that qualify for the 'order.placed' notification. A web
     * order is "new / unfulfilled" only in these three states; placement in any
     * other status (completed, canceled, fraud, closed, …) sends nothing. Other
     * lifecycle events (invoiced / shipped / …) are not gated on this.
     */
    public const PLACED_NOTIFY_STATUSES = ['pending', 'pending_payment', 'processing'];

    public function __construct(
        protected TemplateRenderer $renderer,
        protected ChannelManager $channels,
        protected OrderNotificationMatrix $matrix,
    ) {}

    public function handle(string $bagistoEvent, mixed $payload): void
    {
        // Master kill-switch — set via Configuration → Emails → General → "Enable Fujiba Notifications".
        // Default-enabled when no row in core_config (admin hasn't toggled). Toggling off makes every
        // order event a no-op.
        $enabled = core()->getConfigData('emails.general.fujiba_notification.enabled');
        if ($enabled !== null && ! (int) $enabled) {
            return;
        }

        if (! isset(self::EVENT_MAP[$bagistoEvent])) {
            return;
        }

        try {
            $order = $this->resolveOrder($payload);

            if (! $order) {
                Log::warning('[FujibaNotification] Could not resolve order from event', [
                    'event' => $bagistoEvent,
                ]);
                return;
            }

            $decision = $this->matrix->decide($bagistoEvent, $payload, $order);

            if (! $decision) {
                return;
            }

            $decision['bagisto_event'] = $bagistoEvent;
            $eventKey = $decision['type'];

            // Draft invoices (POS invoice builder) create a real order that is
            // NOT a confirmed sale — suppress the customer-facing 'order placed'
            // notification. Core OrderRepository hard-sets status=pending and
            // fires checkout.order.save.after BEFORE the builder can stamp
            // status='draft', so we can't gate on the column here. Instead the
            // builder sets a request-scoped flag (app('fujiba.suppress_order_placed'))
            // around the create() call. Notifications resume on order.invoiced /
            // order.shipped once the draft is advanced.
            // (See Webkul\Pos\Mutations\Outlet\InvoiceBuilderMutation.)
            if (
                $eventKey === 'order.placed'
                && (app()->bound('fujiba.suppress_order_placed') || $order->status === 'draft')
            ) {
                return;
            }

            if (
                $decision['trigger'] === OrderNotificationMatrix::TRIGGER_STATUS
                && ($skip = $this->statusSkipReason($eventKey, $order))
            ) {
                Log::info('[FujibaNotification] status notification skipped', [
                    'order_id' => $order->id,
                    'type'     => $eventKey,
                    'reason'   => $skip,
                ]);
                return;
            }

            // 'order.placed' is a WEB-only, early-status notification for the
            // customer + admin-Messenger paths. Two gates, both scoped to
            // order.placed so later lifecycle events (invoiced / shipped /
            // cancelled / refunded) still fire normally for any order:
            //   1. Web-only — POS counter sales never trigger an order-placed
            //      alert (customer mail/Messenger AND admin Messenger). The admin
            //      path keeps its own isPosOrder() guard as defence-in-depth.
            //   2. Status gate — only the open/unfulfilled web statuses
            //      (pending / processing / pending_payment) qualify. Anything
            //      else at placement time (e.g. completed, canceled, fraud) sends
            //      nothing.
            //
            // These gates deliberately do NOT apply to the admin-mail path. A
            // counter-only store (every order POS, every order created already
            // 'completed') would otherwise never alert its owner — which is the
            // whole point of that path. Admin mail is opt-in per tenant: it
            // sends only where an admin mail template AND a mail recipient are
            // configured, so stores that want the web-only behaviour are
            // unaffected by having no such rows.
            $webPathsAllowed = true;

            if ($eventKey === 'order.placed') {
                $webPathsAllowed = ! $this->isPosOrder($order)
                    && in_array($order->status, self::PLACED_NOTIFY_STATUSES, true);
            }

            $vars     = $this->buildVars($order, $payload);
            $language = $this->resolveLanguage($order);

            if ($webPathsAllowed) {
                $this->dispatchMail($decision, $order, $vars, $language);
                $this->dispatchMessenger($decision, $order, $vars, $language);
                $this->dispatchAdminNotifications($decision, $order, $vars, $language);
            }

            $this->dispatchAdminMail($decision, $order, $vars, $language);
        } catch (\Throwable $e) {
            // Notification failures must NEVER break the order save flow.
            Log::error('[FujibaNotification] OrderEventListener failed', [
                'event'    => $bagistoEvent,
                'order_id' => isset($order) ? $order?->id : null,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    // -----------------------------------------------------------------------
    // Dispatchers
    // -----------------------------------------------------------------------

    protected function dispatchMail(array $decision, Order $order, array $vars, string $language): void
    {
        $eventKey = $decision['type'];

        $template = NotificationTemplate::resolve($eventKey, 'mail', $language, $order->channel_id, NotificationTemplate::RECIPIENT_CUSTOMER);

        if (! $template) {
            return;
        }

        $email = $order->customer_email ?? $order->customer?->email;

        if (! $email) {
            return;
        }

        $subject  = $this->renderer->render((string) $template->subject, $vars);
        $body     = $this->renderer->render((string) $template->body, $vars);
        $fromName = $template->from_name ? $this->renderer->render($template->from_name, $vars) : null;
        $fromAddr = $template->from_email ? $this->renderer->render($template->from_email, $vars) : null;

        $log = $this->claimLog($decision, $order, [
            'event'                => $eventKey,
            'notification_channel' => 'mail',
            'subject_type'         => Order::class,
            'subject_id'           => $order->id,
            'customer_id'          => $order->customer_id,
            'recipient'            => $email,
            'status'               => NotificationLog::STATUS_PENDING,
            'payload'              => ['language' => $language],
        ]);

        if (! $log) {
            return;
        }

        try {
            $this->channels->get('mail')->send($log, $subject, $body, [
                'order_id'   => $order->id,
                'channel_id' => $order->channel_id,
                'from_name'  => $fromName,
                'from_email' => $fromAddr,
            ]);
            // Mail is queued — actual SMTP delivery happens on the worker.
            // We mark sent here meaning "successfully handed off to the queue".
            $log->markSent();
        } catch (\Throwable $e) {
            $log->markFailed($e->getMessage());
            Log::error('[FujibaNotification] Mail dispatch failed', [
                'event'    => $eventKey,
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    protected function dispatchMessenger(array $decision, Order $order, array $vars, string $language): void
    {
        $eventKey = $decision['type'];

        if (! $order->customer_id) {
            return;
        }

        $template = NotificationTemplate::resolve($eventKey, 'messenger', $language, $order->channel_id, NotificationTemplate::RECIPIENT_CUSTOMER);

        if (! $template) {
            return;
        }

        $tokens = CustomerChannelToken::query()
            ->active()
            ->where('customer_id', $order->customer_id)
            ->where('provider', CustomerChannelToken::PROVIDER_MESSENGER)
            ->get();

        if ($tokens->isEmpty()) {
            return;
        }

        ['body' => $body, 'buttons' => $buttons] = $this->messengerContent($eventKey, $template, $vars, $language);

        foreach ($tokens as $token) {
            $log = $this->claimLog($decision, $order, [
                'event'                => $eventKey,
                'notification_channel' => 'messenger',
                'subject_type'         => Order::class,
                'subject_id'           => $order->id,
                'customer_id'          => $order->customer_id,
                'recipient'            => $token->token,
                'status'               => NotificationLog::STATUS_PENDING,
                'payload'              => [
                    'language'    => $language,
                    'page_id'     => $token->page_id,
                    'template_id' => $template->id,
                ],
            ]);

            if (! $log) {
                continue;
            }

            SendMessengerNotificationJob::dispatch(
                logId: $log->id,
                subject: '',
                body: $body,
                context: [
                    'channel_id'  => $order->channel_id,
                    'page_id'     => $token->page_id,
                    'message_tag' => 'POST_PURCHASE_UPDATE',
                    'buttons'     => $buttons,
                ],
            );
        }
    }

    /**
     * Send admin notifications via Messenger to all active recipients
     * registered for the order's channel. Resolves a separate template with
     * recipient_type='admin' so admin alerts can have a distinct body
     * (e.g. "🔔 New order #1547 — Yamada — ¥4,765") independent of the
     * customer-facing template.
     *
     * Falls back gracefully:
     *   - no admin template configured → silent no-op
     *   - no admin recipients configured for this channel → silent no-op
     */
    protected function dispatchAdminNotifications(array $decision, Order $order, array $vars, string $language): void
    {
        $eventKey = $decision['type'];

        // Suppress admin alerts for POS counter sales — admins see those in
        // the cashier UI, and broadcasting every walk-in to the page admin's
        // Messenger thread is noise. Detect three ways:
        //   1. auth:pos guard active on the current request (POS GraphQL
        //      mutations and POS REST endpoints).
        //   2. Request URL prefixed with /pos (defensive — covers any POS
        //      route that isn't behind auth:pos).
        //   3. pos_order row exists for this order (covers async/queued
        //      replays where the request context is gone).
        // The (1)+(2) checks fire at event time — the order.placed event
        // dispatches inside OrderRepository::create() BEFORE the POS
        // controller writes pos_order, so we can't rely on (3) alone.
        if ($eventKey === 'order.placed' && $this->isPosOrder($order)) {
            return;
        }

        $template = NotificationTemplate::resolve(
            $eventKey,
            'messenger',
            $language,
            $order->channel_id,
            NotificationTemplate::RECIPIENT_ADMIN,
        );

        if (! $template) {
            return;
        }

        $recipients = NotificationAdminRecipient::query()
            ->active()
            ->delivery(NotificationAdminRecipient::DELIVERY_MESSENGER)
            ->forChannel($order->channel_id) // NULL channel_id = fires for every channel
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $rendered = $template->render($vars);
        $body     = (string) ($rendered['body'] ?? '');
        $buttons  = $rendered['buttons'] ?? [];

        foreach ($recipients as $recipient) {
            $log = $this->claimLog($decision, $order, [
                'event'                => $eventKey,
                'notification_channel' => 'messenger',
                'subject_type'         => Order::class,
                'subject_id'           => $order->id,
                'customer_id'          => null, // admin recipient is not a customer
                'recipient'            => $recipient->psid,
                'status'               => NotificationLog::STATUS_PENDING,
                'payload'              => [
                    'language'       => $language,
                    'page_id'        => $recipient->page_id,
                    'recipient_type' => 'admin',
                    'admin_label'    => $recipient->label,
                ],
            ]);

            if (! $log) {
                continue;
            }

            SendMessengerNotificationJob::dispatch(
                logId: $log->id,
                subject: '',
                body: $body,
                context: [
                    'channel_id'  => $order->channel_id,
                    'page_id'     => $recipient->page_id,
                    'message_tag' => 'POST_PURCHASE_UPDATE',
                    'buttons'     => $buttons,
                ],
            );
        }
    }

    /**
     * Email the store's admin recipients about an order.
     *
     * The mirror of dispatchAdminNotifications() for owners who want a plain
     * email rather than a Messenger thread. Recipients are the rows in
     * notification_admin_recipients with delivery_channel='mail', matched on
     * the order's channel (a NULL channel_id row is tenant-wide).
     *
     * Everything here is tenant-scoped: the recipients table, the template
     * table and the order all live in the current tenant's database, so a
     * recipient configured for one store can never be resolved while another
     * store's order is being handled.
     *
     * Falls back silently when no admin mail template or no mail recipient is
     * configured, which is the state of every tenant that hasn't opted in.
     */
    protected function dispatchAdminMail(array $decision, Order $order, array $vars, string $language): void
    {
        $eventKey = $decision['type'];

        $template = NotificationTemplate::resolve(
            $eventKey,
            'mail',
            $language,
            $order->channel_id,
            NotificationTemplate::RECIPIENT_ADMIN,
        );

        if (! $template) {
            return;
        }

        $recipients = NotificationAdminRecipient::query()
            ->active()
            ->delivery(NotificationAdminRecipient::DELIVERY_MAIL)
            ->forChannel($order->channel_id)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $rendered = $template->render($vars);
        $subject  = (string) ($rendered['subject'] ?? '');
        $body     = (string) ($rendered['body'] ?? '');
        $fromName = $rendered['from_name'] ?: null;
        $fromAddr = $rendered['from_email'] ?: null;

        foreach ($recipients as $recipient) {
            $log = $this->claimLog($decision, $order, [
                'event'                => $eventKey,
                'notification_channel' => 'mail',
                'subject_type'         => Order::class,
                'subject_id'           => $order->id,
                'customer_id'          => null, // an admin recipient is not a customer
                'recipient'            => $recipient->email,
                'status'               => NotificationLog::STATUS_PENDING,
                'payload'              => [
                    'language'       => $language,
                    'recipient_type' => 'admin',
                    'admin_label'    => $recipient->label,
                ],
            ]);

            if (! $log) {
                continue;
            }

            try {
                $this->channels->get('mail')->send($log, $subject, $body, [
                    'order_id'   => $order->id,
                    'channel_id' => $order->channel_id,
                    'from_name'  => $fromName,
                    'from_email' => $fromAddr,
                ]);
                // Queued — "sent" here means handed off to the worker.
                $log->markSent();
            } catch (\Throwable $e) {
                // One bad address must not stop the remaining recipients.
                $log->markFailed($e->getMessage());
                Log::error('[FujibaNotification] Admin mail dispatch failed', [
                    'event'     => $eventKey,
                    'order_id'  => $order->id,
                    'recipient' => $recipient->email,
                    'error'     => $e->getMessage(),
                ]);
            }
        }
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Why a status-change notification should NOT go out, or null to send.
     * Every skip is logged by the caller so "why didn't customer Y get it?"
     * has an answer in laravel.log.
     */
    public function statusSkipReason(string $type, Order $order): ?string
    {
        // Counter sales: the customer is standing at the till. Checked by
        // ORIGIN only — staff changing a WEB order's status from the POS app
        // must still notify (the request-context checks in isPosOrder() would
        // wrongly treat that as a POS sale).
        if ($this->isPosOriginOrder($order)) {
            return 'pos_order';
        }

        if (
            in_array($type, OrderNotificationMatrix::NEW_STATUS_TYPES, true)
            && ! config('fujiba-notification.status_updates', true)
        ) {
            return 'status_updates_disabled';
        }

        if ($type === 'order.processing'
            && ($order->invoices()->exists() || $order->shipments()->exists())) {
            // Core flips the order to processing as a side effect of creating
            // an invoice/shipment; the invoiced/shipped message already says it.
            return 'document_message_covers_it';
        }

        if ($type === 'order.pending_payment'
            && NotificationLog::query()
                ->where('event', 'order.placed')
                ->where('subject_type', Order::class)
                ->where('subject_id', $order->id)
                ->where('notification_channel', 'messenger')
                ->exists()) {
            // The order-placed message already asked for payment.
            return 'placed_message_already_sent';
        }

        // Wholesale customers must get their 15% / 20% wholesale price. If the
        // order completes without that discount, hold back the amount message
        // so the customer isn't told a wrong total.
        if ($type === 'order.completed' && ! $this->hasWholesaleDiscount($order)) {
            return 'wholesale_discount_missing';
        }

        return null;
    }

    /**
     * Insert the log row only if this exact notification has never been
     * claimed. Returns null when it has — the caller then sends nothing.
     *
     * The UNIQUE idempotency_key makes this race-safe. For once-per-order
     * types we also honour legacy rows written before the key existed, so an
     * order completed in September is not re-announced on a later re-save.
     */
    protected function claimLog(array $decision, Order $order, array $attributes): ?NotificationLog
    {
        $isAdmin   = ($attributes['payload']['recipient_type'] ?? null) === 'admin';
        $channel   = ($isAdmin ? 'admin-' : '').$attributes['notification_channel'];
        $recipient = (string) $attributes['recipient'];

        if ($decision['scope'] === 'once') {
            $legacy = NotificationLog::query()
                ->whereNull('idempotency_key')
                ->where('event', $decision['type'])
                ->where('subject_type', Order::class)
                ->where('subject_id', $order->id)
                ->where('notification_channel', $attributes['notification_channel'])
                ->where('recipient', $recipient)
                ->exists();

            if ($legacy) {
                return null;
            }
        }

        $attributes['idempotency_key'] = $this->matrix->key($order, $decision, $channel, $recipient);
        $attributes['payload'] = array_merge($attributes['payload'] ?? [], [
            'trigger'       => $decision['bagisto_event'] ?? null,
            'trigger_kind'  => $decision['trigger'],
            'order_status'  => $order->status,
            'message_tag'   => $attributes['notification_channel'] === 'messenger' ? 'POST_PURCHASE_UPDATE' : null,
        ]);

        try {
            return NotificationLog::create($attributes);
        } catch (UniqueConstraintViolationException) {
            Log::info('[FujibaNotification] duplicate suppressed', [
                'order_id' => $order->id,
                'key'      => $attributes['idempotency_key'],
            ]);

            return null;
        }
    }

    /**
     * Final customer Messenger body + buttons. Shared with the sweeper so a
     * re-sent message is identical to the original.
     *
     *  - A line whose value rendered empty ("Tracking: ") is dropped, so a
     *    shipment without a number never shows a blank or invented one.
     *  - order.shipped gets a tracking button only when a real tracking URL
     *    exists and the template doesn't already link it.
     *
     * @return array{body: string, buttons: array}
     */
    public function messengerContent(string $eventKey, NotificationTemplate $template, array $vars, string $language): array
    {
        $rendered = $template->render($vars);
        $buttons  = $rendered['buttons'] ?? [];

        $lines = array_filter(
            preg_split('/\R/u', (string) $template->body),
            function (string $line) use ($vars) {
                if (! preg_match_all('/\{\{\s*[a-zA-Z0-9_.]+\s*\}\}/', $line, $m)) {
                    return true;
                }

                foreach ($m[0] as $placeholder) {
                    if (trim($this->renderer->render($placeholder, $vars)) !== '') {
                        return true;
                    }
                }

                return false;
            },
        );

        $body = trim($this->renderer->render(implode("\n", $lines), $vars));

        $trackingUrl = (string) ($vars['tracking_url'] ?? '');

        if ($eventKey === 'order.shipped' && $trackingUrl !== '' && count($buttons) < 3
            && ! collect($buttons)->contains(fn ($b) => ($b['value'] ?? '') === $trackingUrl)) {
            $labels = ['ja' => '配送状況を確認', 'my' => 'ပါဆယ် ခြေရာခံရန်'];

            $buttons[] = [
                'text'  => $labels[$language] ?? 'Track parcel',
                'type'  => 'url',
                'value' => $trackingUrl,
            ];
        }

        return ['body' => $body, 'buttons' => $buttons];
    }

    /**
     * Whether the order was rung up at a POS till (pos_order row). Unlike
     * isPosOrder(), ignores who is making the current request.
     */
    protected function isPosOriginOrder(Order $order): bool
    {
        return DB::table('pos_order')->where('order_id', $order->id)->exists();
    }

    protected function resolveOrder(mixed $payload): ?Order
    {
        if ($payload instanceof Order) {
            return $payload;
        }

        if ($payload instanceof Invoice || $payload instanceof Shipment || $payload instanceof Refund) {
            return $payload->order;
        }

        return null;
    }

    /**
     * Detect whether the order originates from POS checkout. Must work both
     * inside the request that creates the order (pos_order row not yet
     * written) and on async replays.
     */
    protected function isPosOrder(Order $order): bool
    {
        try {
            if (Auth::guard('pos')->check()) {
                return true;
            }
        } catch (\Throwable $e) {
            // pos guard may not be registered in some contexts (CLI/queues) —
            // fall through to the other detectors.
        }

        $request = request();
        if ($request && method_exists($request, 'is') && $request->is('pos/*', 'pos')) {
            return true;
        }

        return DB::table('pos_order')->where('order_id', $order->id)->exists();
    }

    /**
     * True for non-wholesale customers, or for wholesale orders carrying at
     * least a 15% discount (the 15% / 20% wholesale tiers).
     */
    protected function hasWholesaleDiscount(Order $order): bool
    {
        $groupCode = $order->customer?->group?->code;

        if ($groupCode !== 'wholesale') {
            return true;
        }

        $subTotal = (float) $order->sub_total;

        if ($subTotal <= 0) {
            return false;
        }

        return ((float) $order->discount_amount / $subTotal) >= 0.145;
    }

    protected function resolveLanguage(Order $order): string
    {
        $code = $order->channel?->default_locale?->code;

        if ($code) {
            return strtolower(substr($code, 0, 2));
        }

        return config('fujiba-notification.fallback_language', config('app.locale', 'en'));
    }

    /**
     * Public entry point for rebuilding template vars outside the event flow.
     * Used by the fujiba:notifications:sweep command to re-render stuck
     * notifications with byte-identical variables. Keeps buildVars() protected
     * as the internal contract while exposing a stable name for the sweeper.
     */
    public function buildVarsFor(Order $order, mixed $payload): array
    {
        return $this->buildVars($order, $payload);
    }

    /**
     * Build the variable map exposed to templates via {{dot.notation}}.
     * Empty strings (not nulls) for absent values — null breaks str replace.
     */
    protected function buildVars(Order $order, mixed $payload): array
    {
        $storeName = $order->channel?->name
            ?? optional(core()->getDefaultChannel())->name
            ?? config('app.name');

        $baseUrl = rtrim(config('app.url', ''), '/');

        $trackingNumber = '';
        $trackingUrl    = '';
        $carrier        = '';

        if ($payload instanceof Shipment) {
            $carrier        = trim((string) ($payload->carrier_title ?? ''));
            $trackingNumber = trim((string) ($payload->track_number ?? ''));
            $trackingUrl    = $trackingNumber !== ''
                ? 'https://www.17track.net/en/track?nums=' . urlencode($trackingNumber)
                : '';
        }

        $productList = '';
        if (! empty($order->items)) {
            $lines = [];
            foreach ($order->items as $item) {
                $lines[] = ($item->name ?? 'Item') . ' × ' . (int) ($item->qty_ordered ?? 1);
            }
            $productList = implode(', ', $lines);
        }

        // Signed auto-login URL: 7-day expiry. The OrderTrackController logs
        // the customer in and redirects to /customer/account/orders/view/{id}.
        // The URL host is forced to the order's channel hostname (e.g.
        // mmfood.fujiba.co.jp vs kimono.fujiba.co.jp) so customers don't see
        // a cross-domain link that looks like phishing. forceRootUrl() mutates
        // the singleton URL generator — the try/finally guarantees we reset
        // it even on throw, otherwise the next job in the worker would
        // generate links against the wrong host.
        // Guest orders (no customer_id) get an empty action_url — they have
        // no account page to land on.
        $actionUrl = '';
        if ($order->customer_id) {
            $tenantHost = $order->channel?->hostname
                ?: parse_url(config('app.url', ''), PHP_URL_HOST);

            try {
                if ($tenantHost) {
                    URL::forceRootUrl('https://' . $tenantHost);
                }
                $actionUrl = URL::temporarySignedRoute(
                    'fujiba.notification.order.track',
                    now()->addDays(7),
                    ['orderId' => $order->id],
                );
            } catch (\Throwable $e) {
                Log::warning('[FujibaNotification] Failed to generate signed order-track URL', [
                    'order_id' => $order->id,
                    'error'    => $e->getMessage(),
                ]);
            } finally {
                // Always reset, even on throw, to prevent host pollution
                // into the next request/job sharing this UrlGenerator.
                URL::forceRootUrl(null);
            }
        }

        return [
            'order' => [
                'id'              => $order->id,
                'increment_id'    => $order->increment_id ?? '',
                'grand_total'     => core()->formatPrice((float) $order->grand_total, $order->order_currency_code),
                'currency'        => $order->order_currency_code ?? '',
                'status'          => $order->status ?? '',
                'date'            => optional($order->created_at)->format('Y-m-d') ?? '',
                'tracking_number' => $trackingNumber,
                'tracking_url'    => $trackingUrl,
                'carrier'         => $carrier,
                'product_list'    => $productList,
                'action_url'      => $actionUrl,
            ],
            'customer' => [
                'first_name' => $order->customer_first_name ?? '',
                'last_name'  => $order->customer_last_name ?? '',
                'email'      => $order->customer_email ?? '',
                'full_name'  => trim(($order->customer_first_name ?? '') . ' ' . ($order->customer_last_name ?? '')),
            ],
            'store' => [
                'name' => $storeName,
                'url'  => $baseUrl,
            ],
            // Top-level shortcuts for the most common placeholders.
            'order_id'        => $order->increment_id ?? $order->id,
            'order_total'     => core()->formatPrice((float) $order->grand_total, $order->order_currency_code),
            'tracking_number' => $trackingNumber,
            'tracking_url'    => $trackingUrl,
            'carrier'         => $carrier,
            'action_url'      => $actionUrl,
            'product_list'    => $productList,
            'store_name'      => $storeName,
            'customer_name'   => trim(($order->customer_first_name ?? '') . ' ' . ($order->customer_last_name ?? '')) ?: 'Customer',
        ];
    }
}
