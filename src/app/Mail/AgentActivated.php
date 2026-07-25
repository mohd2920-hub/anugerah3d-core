<?php

namespace App\Mail;

use App\Models\Agent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgentActivated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Agent $agent,
        public ?Agent $referrer,
        public string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Anugerah3D agent account has been approved',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.agent-activated',
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [];
    }
}
