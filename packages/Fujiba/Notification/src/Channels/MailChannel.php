<?php

namespace Fujiba\Notification\Channels;

use Fujiba\Notification\Channels\Contracts\Channel;
use Fujiba\Notification\Mail\OrderPlacedMail;
use Fujiba\Notification\Models\NotificationLog;
use Illuminate\Support\Facades\Mail;

class MailChannel implements Channel
{
    public function key(): string
    {
        return 'mail';
    }

    public function send(NotificationLog $log, string $subject, string $body, array $context = []): void
    {
        $email = $log->recipient;

        if (! $email) {
            throw new \RuntimeException('Mail recipient email missing');
        }

        $mailable = new OrderPlacedMail(
            renderedSubject: $subject,
            renderedBody: $body,
            fromName: $context['from_name'] ?? null,
            fromEmail: $context['from_email'] ?? null,
            context: $context + ['subject' => $subject],
        );

        Mail::to($email)->queue($mailable);
    }
}
