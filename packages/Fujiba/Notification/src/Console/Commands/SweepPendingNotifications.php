<?php

namespace Fujiba\Notification\Console\Commands;

use Fujiba\Notification\Jobs\SendMessengerNotificationJob;
use Fujiba\Notification\Listeners\OrderEventListener;
use Fujiba\Notification\Models\NotificationLog;
use Fujiba\Notification\Models\NotificationTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\Refund;
use Webkul\Sales\Models\Shipment;

/**
 * Safety net (defense layer 3) for Messenger notifications.
 *
 * Layers 1 + 2 (config/queue.php after_commit + SendMessengerNotificationJob
 * release-on-missing-row) close the known transaction race. This sweeper is the
 * catch-all that makes a lost notification IMPOSSIBLE even if a worker crashes
 * mid-job, Redis drops a job, or a future regression re-introduces early dispatch:
 * any messenger NotificationLog still 'pending' past a short grace window is
 * re-dispatched here.
 *
 * Scoped to the tenant DB — run via the scheduler closure in bootstrap/app.php,
 * which initializes each tenant before calling this. Idempotent: the job marks
 * the row 'sent' on success and SendMessengerNotificationJob short-circuits on
 * an already-sent row, so a re-sweep before the worker finishes won't double-send
 * the same row twice (the grace window also keeps in-flight rows out of scope).
 */
class SweepPendingNotifications extends Command
{
    protected $signature = 'fujiba:notifications:sweep
                            {--grace=2 : Minimum age in minutes before a pending row is re-dispatched}
                            {--max-age=1440 : Rows older than this (minutes) are archived as failed instead of re-sent (default 24h). 0 = no ceiling}
                            {--limit=200 : Max rows to re-dispatch per run}
                            {--dry-run : Report what would be re-dispatched without queuing}';

    protected $description = 'Re-dispatch Messenger notifications stuck in pending past the grace window';

    public function handle(): int
    {
        $grace   = (int) $this->option('grace');
        $maxAge  = (int) $this->option('max-age');
        $limit   = (int) $this->option('limit');
        $dryRun  = (bool) $this->option('dry-run');
        $cutoff  = now()->subMinutes(max($grace, 1));

        // Archive notifications too old to be worth delivering. Re-sending an
        // "order placed" ping for an order from days ago confuses customers and
        // admins; these rows are stranded backlog from before the race was fixed.
        // Mark them 'failed' so they leave the pending pool (and show in
        // monitoring) without firing. max-age=0 disables the ceiling.
        $archived = 0;

        if ($maxAge > 0) {
            $ageCutoff = now()->subMinutes($maxAge);

            $oldQuery = NotificationLog::query()
                ->where('notification_channel', 'messenger')
                ->where('status', NotificationLog::STATUS_PENDING)
                ->where('created_at', '<', $ageCutoff);

            $archived = (clone $oldQuery)->count();

            if ($archived > 0 && ! $dryRun) {
                $oldQuery->update([
                    'status' => NotificationLog::STATUS_FAILED,
                    'error'  => 'Sweep: archived as stale backlog (older than max-age, never delivered).',
                ]);
            }

            if ($archived > 0) {
                $this->warn(sprintf(
                    '%sArchived %d stale pending notification(s) older than %d min as failed.',
                    $dryRun ? '[DRY RUN] ' : '',
                    $archived,
                    $maxAge,
                ));
            }
        }

        // Only messenger rows: the mail channel marks 'sent' synchronously at
        // queue-handoff time (see OrderEventListener::dispatchMail) so a pending
        // mail row means the queue push itself threw — re-sweeping wouldn't help.
        // Window: older than the grace floor (let in-flight jobs finish), newer
        // than the max-age ceiling (don't resurrect stale backlog).
        $stuck = NotificationLog::query()
            ->where('notification_channel', 'messenger')
            ->where('status', NotificationLog::STATUS_PENDING)
            ->where('created_at', '<=', $cutoff)
            ->when($maxAge > 0, fn ($q) => $q->where('created_at', '>=', now()->subMinutes($maxAge)))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($stuck->isEmpty()) {
            $this->info('No stuck pending messenger notifications in the send window.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s%d stuck pending messenger notification(s) older than %d min',
            $dryRun ? '[DRY RUN] ' : '',
            $stuck->count(),
            $grace,
        ));

        $redispatched = 0;
        $skipped      = 0;

        foreach ($stuck as $log) {
            $context = $this->rebuild($log);

            if ($context === null) {
                // Couldn't rebuild (order/template gone). Mark failed so it
                // leaves the pending pool and shows up in monitoring instead
                // of being swept forever.
                $skipped++;

                if (! $dryRun) {
                    $log->markFailed('Sweep: could not rebuild context (order or template missing).');
                }

                continue;
            }

            if ($dryRun) {
                $this->line(sprintf('  would re-dispatch log #%d → %s (%s)', $log->id, $log->recipient, $log->event));
                $redispatched++;

                continue;
            }

            SendMessengerNotificationJob::dispatch(
                logId: $log->id,
                subject: '',
                body: $context['body'],
                context: $context['context'],
            );

            $redispatched++;
        }

        Log::info('[FujibaNotification] sweep complete', [
            'redispatched' => $redispatched,
            'skipped'      => $skipped,
            'grace_min'    => $grace,
        ]);

        $this->info(sprintf('Re-dispatched %d, skipped %d.', $redispatched, $skipped));

        return self::SUCCESS;
    }

    /**
     * Rebuild the body + send context for a stuck log by re-resolving its
     * template against the live order and re-rendering. Returns null when the
     * order or a matching template no longer exists.
     *
     * @return array{body: string, context: array<string, mixed>}|null
     */
    protected function rebuild(NotificationLog $log): ?array
    {
        $order = $this->resolveOrder($log);

        if (! $order) {
            return null;
        }

        $payload       = $log->payload ?? [];
        $language      = $payload['language'] ?? 'en';
        $recipientType = ($payload['recipient_type'] ?? null) === 'admin'
            ? NotificationTemplate::RECIPIENT_ADMIN
            : NotificationTemplate::RECIPIENT_CUSTOMER;

        $template = NotificationTemplate::resolve(
            $log->event,
            'messenger',
            $language,
            $order->channel_id,
            $recipientType,
        );

        if (! $template) {
            return null;
        }

        // Reuse the listener's variable builder so re-rendered bodies are byte
        // identical to the original dispatch (same {{order.id}}, {{action_url}},
        // tracking links, etc.). buildVars() is the single source of truth.
        $listener = app(OrderEventListener::class);
        $vars     = $listener->buildVarsFor($order, $this->resolvePayloadSubject($log, $order));
        $rendered = $recipientType === NotificationTemplate::RECIPIENT_CUSTOMER
            ? $listener->messengerContent($log->event, $template, $vars, $language)
            : $template->render($vars);

        return [
            'body'    => (string) ($rendered['body'] ?? ''),
            'context' => [
                'channel_id'  => $order->channel_id,
                'page_id'     => $payload['page_id'] ?? null,
                'message_tag' => 'POST_PURCHASE_UPDATE',
                'buttons'     => $rendered['buttons'] ?? [],
            ],
        ];
    }

    protected function resolveOrder(NotificationLog $log): ?Order
    {
        if ($log->subject_type === Order::class) {
            return Order::find($log->subject_id);
        }

        return null;
    }

    /**
     * order.invoiced / order.shipped / order.refunded templates render
     * payload-specific vars (tracking number from the Shipment, etc.).
     * Re-resolve the most recent matching child entity so those vars repopulate;
     * fall back to the order itself for order.placed / order.cancelled.
     */
    protected function resolvePayloadSubject(NotificationLog $log, Order $order): mixed
    {
        return match ($log->event) {
            'order.invoiced' => $order->invoices()->latest('id')->first() ?? $order,
            'order.shipped'  => $order->shipments()->latest('id')->first() ?? $order,
            'order.refunded' => $order->refunds()->latest('id')->first() ?? $order,
            default          => $order,
        };
    }
}
