<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantImpersonationStarted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $tenantId,
        public string $domain,
        public string $superAdminEmail,
        public string $reason,
        public $startedAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[snssale] Super-admin started impersonation on {$this->domain}",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->body(),
        );
    }

    private function body(): string
    {
        $when = is_object($this->startedAt) ? $this->startedAt->format('Y-m-d H:i:s') : (string) $this->startedAt;
        return <<<HTML
<p>This is an automated notice.</p>
<p>The snssale platform super-admin <strong>{$this->superAdminEmail}</strong> has started an impersonation session on your tenant <strong>{$this->domain}</strong>.</p>
<ul>
    <li><strong>Reason given:</strong> {$this->reason}</li>
    <li><strong>Started at:</strong> {$when}</li>
</ul>
<p>Every action taken during this session is audit-logged. If you did not request this assistance, reply to this email immediately.</p>
HTML;
    }
}
