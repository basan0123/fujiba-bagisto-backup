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

    public int $tries   = 3;

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

        if (! $log || $log->status === NotificationLog::STATUS_SENT) {
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
