<?php

namespace App\Mail\Agent;

use App\Models\WeeklyClosingAgentSummary;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyClosingPaymentMadeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public int $summaryId) {}

    public function envelope(): Envelope
    {
        $summary = WeeklyClosingAgentSummary::query()->findOrFail($this->summaryId);
        $firstName = explode(' ', trim((string) $summary->agent_name))[0] ?: 'Agent';

        return new Envelope(
            subject: '['.$firstName.'] Weekly closing payout has been paid',
        );
    }

    public function content(): Content
    {
        $summary = WeeklyClosingAgentSummary::query()
            ->with('closing:id,week_key,period_start,period_end')
            ->findOrFail($this->summaryId);

        return new Content(
            markdown: 'mail.agent.weekly-closing-payment-made',
            with: ['summary' => $summary],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
