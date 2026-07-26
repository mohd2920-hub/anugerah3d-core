<?php

namespace App\Mail\Admin;

use App\Models\WeeklyClosing;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyClosingSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public int $closingId) {}

    public function envelope(): Envelope
    {
        $closing = WeeklyClosing::query()->findOrFail($this->closingId);

        return new Envelope(
            subject: '[Admin] Weekly closing summary '.$closing->week_key,
        );
    }

    public function content(): Content
    {
        $closing = WeeklyClosing::query()
            ->with(['agentSummaries' => fn ($query) => $query->orderByDesc('total_bonus')->limit(10)])
            ->findOrFail($this->closingId);

        return new Content(
            markdown: 'mail.admin.weekly-closing-summary',
            with: [
                'closing' => $closing,
                'topPayouts' => $closing->agentSummaries,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
