<?php

namespace App\Mail\Admin;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgentOrderPlacedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public int $orderId, public string $orderNumber) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New Agent Order: {$this->orderNumber}",
        );
    }

    public function content(): Content
    {
        $order = Order::query()
            ->with([
                'agent:id,agt_name,login_id,email,phone_number',
                'items:id,order_id,product_code,product_name,quantity,unit_selling_price,discount_percentage,unit_price,line_total,is_preorder',
            ])
            ->findOrFail($this->orderId);

        return new Content(
            markdown: 'mail.admin.agent-order-placed',
            with: ['order' => $order],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
