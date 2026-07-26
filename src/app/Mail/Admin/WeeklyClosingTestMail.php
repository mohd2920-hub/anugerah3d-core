<?php

namespace App\Mail\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyClosingTestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public array $payload,
        public string $subjectPrefix,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('%s Weekly closing %s', $this->subjectPrefix, (string) ($this->payload['period_label'] ?? 'summary')),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.admin.weekly-closing-test',
            with: [
                'data' => $this->payload,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
