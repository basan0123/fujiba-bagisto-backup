<?php

namespace Fujiba\Notification\Listeners;

use Fujiba\Notification\Jobs\SendMessengerNotificationJob;
use Fujiba\Notification\Models\CustomerChannelToken;
use Fujiba\Notification\Models\NotificationAdminRecipient;
use Fujiba\Notification\Models\NotificationLog;
use Fujiba\Notification\Models\NotificationTemplate;
use Fujiba\Notification\Services\ChannelManager;
use Fujiba\Notification\Services\TemplateRenderer;
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
    public const EVENT_MAP = [
        'checkout.order.save.after' => 'order.placed',
        'sales.invoice.save.after'  => 'order.invoiced',
        'sales.shipment.save.after' => 'order.shipped',
        'sales.order.cancel.after'  => 'order.cancelled',
        'sales.refund.save.after'   => 'order.refunded',
    ];

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

        $eventKey = self::EVENT_MAP[$bagistoEvent] ?? null;

        if (! $eventKey) {
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

            // 'order.placed' is a WEB-only, early-status notification. Two gates,
            // both scoped to order.placed so later lifecycle events (invoiced /
            // shipped / cancelled / refunded) still fire normally for any order:
            //   1. Web-only — POS counter sales never trigger an order-placed
            //      alert (customer mail/Messenger AND admin Messenger). The admin
            //      path keeps its own isPosOrder() guard as defence-in-depth.
            //   2. Status gate — only the open/unfulfilled web statuses
            //      (pending / processing / pending_payment) qualify. Anything
            //      else at placement time (e.g. completed, canceled, fraud) sends
            //      nothing.
            if ($eventKey === 'order.placed') {
                if ($this->isPosOrder($order)) {
                    return;
                }

                if (! in_array($order->status, self::PLACED_NOTIFY_STATUSES, true)) {
                    return;
                }
            }

            $vars     = $this->buildVars($order, $payload);
            $language = $this->resolveLanguage($order);

            $this->dispatchMail($eventKey, $order, $vars, $language);
            $this->dispatchMessenger($eventKey, $order, $vars, $language);
            $this->dispatchAdminNotifications($eventKey, $order, $vars, $language);
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

    protected function dispatchMail(string $eventKey, Order $order, array $vars, string $language): void
    {
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

        $log = NotificationLog::create([
            'event'                => $eventKey,
            'notification_channel' => 'mail',
            'subject_type'         => Order::class,
            'subject_id'           => $order->id,
            'customer_id'          => $order->customer_id,
            'recipient'            => $email,
            'status'               => NotificationLog::STATUS_PENDING,
            'payload'              => ['language' => $language],
        ]);

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

    protected function dispatchMessenger(string $eventKey, Order $order, array $vars, string $language): void
    {
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

        // Use the model's render() so buttons are placeholder-replaced together
        // with body — keeps body and button text/URLs in sync.
        $rendered = $template->render($vars);
        $body     = (string) ($rendered['body'] ?? '');
        $buttons  = $rendered['buttons'] ?? [];

        foreach ($tokens as $token) {
            $log = NotificationLog::create([
                'event'                => $eventKey,
                'notification_channel' => 'messenger',
                'subject_type'         => Order::class,
                'subject_id'           => $order->id,
                'customer_id'          => $order->customer_id,
                'recipient'            => $token->token,
                'status'               => NotificationLog::STATUS_PENDING,
                'payload'              => [
                    'language' => $language,
                    'page_id'  => $token->page_id,
                ],
            ]);

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
    protected function dispatchAdminNotifications(string $eventKey, Order $order, array $vars, string $language): void
    {
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
            ->where(function ($q) use ($order) {
                $q->where('channel_id', $order->channel_id)
                  ->orWhereNull('channel_id'); // global admin recipients fire for every channel
            })
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $rendered = $template->render($vars);
        $body     = (string) ($rendered['body'] ?? '');
        $buttons  = $rendered['buttons'] ?? [];

        foreach ($recipients as $recipient) {
            $log = NotificationLog::create([
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

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

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

        if ($payload instanceof Shipment) {
            $trackingNumber = (string) ($payload->track_number ?? '');
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
            'action_url'      => $actionUrl,
            'product_list'    => $productList,
            'store_name'      => $storeName,
            'customer_name'   => trim(($order->customer_first_name ?? '') . ' ' . ($order->customer_last_name ?? '')) ?: 'Customer',
        ];
    }
}
