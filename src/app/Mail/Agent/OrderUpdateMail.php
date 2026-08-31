<?php

namespace App\Mail\Agent;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $orderId,
        public string $orderNumber,
        public string $updateType,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->updateType) {
            'submitted' => "Your Anugerah3D order is confirmed: {$this->orderNumber}",
            'processing' => "Your order is now processing: {$this->orderNumber}",
            'completed' => "Your order is complete: {$this->orderNumber}",
            'cancelled' => "Your order was cancelled: {$this->orderNumber}",
            'payment_updated' => "Order payment status updated: {$this->orderNumber}",
            default => "Order update: {$this->orderNumber}",
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $order = Order::query()
            ->with([
                'agent:id,agt_name,email',
                'items:id,order_id,product_code,product_name,quantity,clicker_character_count,clicker_characters,clicker_casing_image_path,clicker_huruf_image_path,unit_price,line_total,is_preorder',
            ])
            ->findOrFail($this->orderId);

        return new Content(
            markdown: 'mail.agent.order-update',
            with: [
                'order' => $order,
                'updateType' => $this->updateType,
                'historyUrl' => route('agent.history'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
