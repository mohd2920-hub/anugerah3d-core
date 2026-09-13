<?php

namespace App\Mail\Admin;

use App\Models\CustomerOrder;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CustomerOrderPlacedMail extends Mailable
{
    public function __construct(public int $orderId) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New customer catalogue order');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.admin.customer-order-placed', with: ['order' => CustomerOrder::with('agent')->findOrFail($this->orderId)]);
    }
}
