<?php

namespace App\Mail;

use App\Models\Agent;
use App\Models\AgentEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgentTemplateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AgentEmailTemplate $template,
        public Agent $recipient,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->template->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: "emails.agent-template",
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
