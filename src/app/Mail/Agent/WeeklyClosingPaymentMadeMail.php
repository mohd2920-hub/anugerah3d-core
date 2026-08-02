<?php

namespace App\Mail\Agent;

use App\Models\WeeklyClosingAgentSummary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyClosingPaymentMadeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public int $summaryId)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        $summary = WeeklyClosingAgentSummary::query()
            ->with('closing:id,week_key')
            ->findOrFail($this->summaryId);

        return new Envelope(
            subject: 'Bayaran Weekly Closing '.$summary->closing->week_key.' Berjaya - RM '.number_format((float) $summary->total_bonus, 2),
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
