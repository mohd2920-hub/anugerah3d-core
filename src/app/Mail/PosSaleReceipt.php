<?php

namespace App\Mail;

use App\Models\PosSale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PosSaleReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PosSale $sale) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Anugerah3D receipt — '.$this->sale->sale_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pos-sale-receipt',
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
