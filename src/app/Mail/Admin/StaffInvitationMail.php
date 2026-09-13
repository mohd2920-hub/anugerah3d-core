<?php

namespace App\Mail\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $staffName, public string $invitationUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Anugerah3D staff invitation');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.admin.staff-invitation');
    }
}
