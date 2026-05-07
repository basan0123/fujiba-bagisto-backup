<?php

namespace Fujiba\Notification\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Generic rendered-template mailable. Despite the legacy class name,
 * carries any order-lifecycle event (placed, invoiced, shipped, etc.).
 * Queued via QueueTenancyBootstrapper so it picks up the tenant DB context
 * on the worker side automatically.
 */
class OrderPlacedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $renderedSubject,
        public string $renderedBody,
        public ?string $fromName = null,
        public ?string $fromEmail = null,
        public array $context = [],
    ) {
        $this->queue = config('fujiba-notification.queue', 'default');
    }

    public function envelope(): Envelope
    {
        $fromEmail = $this->fromEmail ?: config('mail.from.address');
        $fromName  = $this->fromName  ?: config('mail.from.name');

        return new Envelope(
            from: new Address($fromEmail, $fromName ?? ''),
            subject: $this->renderedSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'fujiba_notification::emails.rendered',
            with: [
                'body'    => $this->renderedBody,
                'context' => $this->context + ['subject' => $this->renderedSubject],
            ],
        );
    }
}
