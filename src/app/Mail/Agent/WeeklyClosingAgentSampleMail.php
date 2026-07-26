<?php

namespace App\Mail\Agent;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyClosingAgentSampleMail extends Mailable
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
            subject: sprintf('%s Weekly closing performance summary (%s)', $this->subjectPrefix, (string) ($this->payload['period_label'] ?? 'weekly period')),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.agent.weekly-closing-sample',
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
