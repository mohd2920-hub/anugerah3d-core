<?php

namespace App\Mail;

use App\Models\Agent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgentRegistrationAdminNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Agent $agent,
        public Agent $referrer,
        public string $reviewUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New agent registration pending approval — '.$this->agent->agt_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.agent-registration-admin-notification',
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [];
    }
}
