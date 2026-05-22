<?php

namespace Webkul\Pos\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QualifiedInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $invoice, public string $pdfPath) {}

    public function envelope(): Envelope
    {
        $fromAddress = config('mail.from.address') ?: 'noreply@example.com';
        $fromName = $this->invoice['issuerName'] ?: (config('mail.from.name') ?: 'POS');

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: '【適格請求書】No. '.($this->invoice['incrementId'] ?? $this->invoice['id']),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'pos::emails.qualified-invoice-email',
            with: ['invoice' => $this->invoice],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as('invoice-'.$this->invoice['id'].'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
