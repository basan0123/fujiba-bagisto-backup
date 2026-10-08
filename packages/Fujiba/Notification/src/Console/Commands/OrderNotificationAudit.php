<?php

namespace Fujiba\Notification\Console\Commands;

use Fujiba\Notification\Listeners\OrderEventListener;
use Fujiba\Notification\Models\CustomerChannelToken;
use Fujiba\Notification\Models\NotificationLog;
use Fujiba\Notification\Services\OrderNotificationMatrix;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Webkul\Sales\Models\Order;

/**
 * Answers "why did customer X get / not get this message?" per order.
 *
 *   php artisan tenants:run fujiba:orders:notification-audit --option="days=30"
 *   php artisan tenants:run fujiba:orders:notification-audit --option="order=1790"
 *
 * With --backfill it lists web orders whose CURRENT status message was never
 * sent. Nothing is sent unless --send is also given; even then each order
 * goes through the normal listener, so the matrix rules and duplicate
 * protection still apply.
 */
class OrderNotificationAudit extends Command
{
    protected $signature = 'fujiba:orders:notification-audit
        {--order= : One order increment id}
        {--days=30 : Look-back window}
        {--backfill : List orders whose current-status message is missing}
        {--send : With --backfill, actually send (default dry-run)}';

    protected $description = 'Audit customer Messenger order notifications (read-only unless --backfill --send)';

    public function handle(OrderNotificationMatrix $matrix): int
    {
        $orders = Order::query()
            ->whereNotIn('id', DB::table('pos_order')->whereNotNull('order_id')->select('order_id'))
            ->when($this->option('order'), fn ($q, $inc) => $q->where('increment_id', $inc))
            ->unless($this->option('order'), fn ($q) => $q->where('created_at', '>=', now()->subDays((int) $this->option('days'))))
            ->orderBy('id')
            ->get();

        $rows    = [];
        $missing = [];

        foreach ($orders as $order) {
            $hasPsid = $order->customer_id && CustomerChannelToken::query()->active()
                ->where('customer_id', $order->customer_id)
                ->where('provider', CustomerChannelToken::PROVIDER_MESSENGER)
                ->exists();

            $logs = NotificationLog::query()
                ->where('subject_type', Order::class)
                ->where('subject_id', $order->id)
                ->where('notification_channel', 'messenger')
                ->whereNotNull('customer_id')
                ->orderBy('id')
                ->get();

            $sent = $logs->map(fn ($l) => sprintf(
                '%s:%s%s',
                substr($l->event, 6),
                $l->status,
                ! empty($l->payload['facebook_message_id']) ? '✓fb' : '',
            ))->implode(' ');

            $current = OrderNotificationMatrix::STATUS_TYPES[$order->status] ?? null;
            $skip    = $current ? app(OrderEventListener::class)->statusSkipReason($current, $order) : null;
            $gap     = $current && ! $skip && ! $logs->contains('event', $current);

            if ($gap) {
                $missing[] = $order;
            }

            $rows[] = [
                $order->increment_id,
                $order->status,
                $order->updated_at?->format('m-d H:i'),
                $hasPsid ? 'yes' : 'NO (cannot message)',
                $sent ?: '—',
                $gap ? substr($current, 6).' not sent' : ($skip && ! $logs->contains('event', $current) ? 'by design: '.$skip : ''),
            ];

            if ($this->option('order')) {
                foreach ($logs as $l) {
                    $this->line(sprintf(
                        '  #%d %s %s trigger=%s tag=%s fb=%s key=%s err=%s',
                        $l->id, $l->created_at, $l->event,
                        $l->payload['trigger'] ?? 'legacy',
                        $l->payload['message_tag'] ?? 'POST_PURCHASE_UPDATE',
                        $l->payload['facebook_message_id'] ?? '-',
                        $l->idempotency_key ?? '-',
                        $l->error ?? '-',
                    ));
                }
            }
        }

        $this->table(['Order', 'Status', 'Updated', 'Messenger linked', 'Sent (customer)', 'Gap'], $rows);
        $this->line(count($missing).' order(s) whose current-status message was never sent. Skip reasons for live events are in laravel.log ("status notification skipped").');

        if (! $this->option('backfill')) {
            return self::SUCCESS;
        }

        if (! $this->option('send')) {
            $this->warn('DRY-RUN: nothing sent. Re-run with --backfill --send to notify these customers.');

            return self::SUCCESS;
        }

        foreach ($missing as $order) {
            app(OrderEventListener::class)->handle(OrderNotificationMatrix::STATUS_EVENT, $order);
            $this->line("  dispatched current-status message for #{$order->increment_id}");
        }

        return self::SUCCESS;
    }
}
