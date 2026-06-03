<?php

namespace Fujiba\Notification\Jobs;

use Fujiba\Notification\Models\NotificationLog;
use Fujiba\Notification\Services\ChannelManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMessengerNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 5;

    public int $backoff = 60;

    public function __construct(
        public readonly int $logId,
        public readonly string $subject,
        public readonly string $body,
        public readonly array $context = [],
    ) {
        $this->queue = config('fujiba-notification.queue', 'default');
    }

    public function handle(ChannelManager $channels): void
    {
        $log = NotificationLog::find($this->logId);

        // Row not visible yet. This used to be a silent return — the single
        // biggest source of lost notifications: when the job was dispatched
        // from inside OrderRepository's still-open transaction, the worker
        // raced ahead of the commit, find() returned null, and the alert
        // vanished with status stuck at 'pending'. after_commit (config/queue.php)
        // now prevents that at the source, but we NEVER silently drop again:
        // release the job back to the queue so it retries once the row commits.
        // Only after $tries attempts (≈5 min of backoff) do we give up — by
        // then the row is genuinely gone (manually deleted), not just uncommitted.
        if (! $log) {
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff);

                return;
            }

            // Exhausted retries with no row — nothing left to send or mark.
            return;
        }

        if ($log->status === NotificationLog::STATUS_SENT) {
            return;
        }

        try {
            $channels->get('messenger')->send($log, $this->subject, $this->body, $this->context);
            $log->markSent();
        } catch (\Throwable $e) {
            $log->markFailed($e->getMessage());

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        $log = NotificationLog::find($this->logId);
        $log?->markFailed('Job failed permanently: '.$e->getMessage());
    }
}
