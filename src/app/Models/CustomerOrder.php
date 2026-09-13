<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'tracking_token',
    'commission_rate',
    'commission_amount',
    'refunded_product_amount',
    'idempotency_key',
    'order_number',
    'agent_id',
    'status',
    'fulfilment_method',
    'recipient_name',
    'phone_number',
    'delivery_address',
    'notes',
    'payment_method',
    'payment_proof_paths',
    'payment_status',
    'subtotal',
    'delivery_fee',
    'total_amount',
    'total_units',
    'placed_at',
    'admin_notification_sent_at',
    'agent_submission_email_sent_at',
    'inventory_reserved_at',
    'processed_at',
    'completed_at',
    'cancelled_at',
])]
class CustomerOrder extends Order
{
    protected $table = 'customer_orders';

    public function customerWhatsAppUrl(): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) $this->phone_number);
        if ($phone === '') {
            return null;
        }
        if (str_starts_with($phone, '0')) {
            $phone = '60'.substr($phone, 1);
        }
        $lines = [
            'Salam '.$this->recipient_name.',',
            'Makluman pesanan daripada Anugerah3D.',
            'No. pesanan: '.$this->order_number,
            'Status: '.$this->statusLabel(),
            'Bayaran: '.$this->paymentStatusLabel(),
            'Jumlah: RM '.number_format((float) $this->total_amount, 2),
            'Cara penerimaan: '.($this->fulfilment_method === 'delivery' ? 'Penghantaran' : 'Ambil sendiri'),
        ];
        if ($this->tracking_number) {
            $lines[] = 'Kurier: '.$this->courier;
            $lines[] = 'Nombor tracking: '.$this->tracking_number;
        }
        if ($this->customer_received_at) {
            $lines[] = 'Penerimaan barang telah disahkan. Terima kasih. Pautan pesanan telah tamat.';
        } else {
            $lines[] = 'Semak perkembangan pesanan: '.route('agent.customer.status', $this->tracking_token);
        }

        return 'https://wa.me/'.$phone.'?text='.rawurlencode(implode("\n", $lines));
    }

    public function canConfirmReceipt(): bool
    {
        return array_key_exists('customer_received_at', $this->getAttributes())
            && ! $this->customer_received_at
            && $this->payment_status === 'paid'
            && ($this->status === 'completed' || ($this->status === 'shipped' && $this->fulfilment_method === 'delivery'));
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Diterima', 'processing' => 'Diproses', 'ready' => 'Siap',
            'shipped' => 'Dihantar', 'pickup_ready' => 'Sedia Diambil', 'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan', default => parent::statusLabel(),
        };
    }

    public function paymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            'paid' => 'Bayaran disahkan', 'refunded' => 'Bayaran dipulangkan',
            default => $this->payment_proof_requested ? 'Bukti bayaran perlu dikemas kini' : ($this->paymentProofPaths() ? 'Menunggu pengesahan bayaran' : 'Menunggu bukti bayaran'),
        };
    }

    protected function casts(): array
    {
        return [...parent::casts(), 'customer_received_at' => 'datetime',
            'receipt_snapshot' => 'array', 'payment_proof_requested' => 'boolean', 'payment_confirmed_at' => 'datetime', 'ready_at' => 'datetime', 'shipped_at' => 'datetime', 'pickup_ready_at' => 'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerOrderItem::class, 'order_id')->orderBy('id');
    }

    public function earnedCommissionCents(): int
    {
        if ($this->status === self::StatusCancelled || $this->payment_status === self::PaymentStatusRefunded) {
            return 0;
        }
        $base = max(0, (int) round((float) $this->subtotal * 100) - (int) round((float) $this->refunded_product_amount * 100));

        return (int) round($base * (float) $this->commission_rate / 100);
    }

    public function paidCommissionCents(): int
    {
        return (int) round((float) DB::table('customer_commission_entries')->where('order_id', $this->id)->where('type', 'payment')->sum('amount') * 100);
    }

    public function commissionStatus(): string
    {
        $earned = $this->earnedCommissionCents();
        $paid = $this->paidCommissionCents();
        if ($paid > $earned) {
            return 'Adjustment due';
        }
        if ($earned === 0) {
            return 'Cancelled';
        }
        if ($paid >= $earned) {
            return 'Paid';
        }

        return ($this->status === self::StatusCompleted || ($this->status === 'shipped' && $this->fulfilment_method === 'delivery')) && $this->payment_status === self::PaymentStatusPaid ? 'Eligible' : 'Pending';
    }
}
