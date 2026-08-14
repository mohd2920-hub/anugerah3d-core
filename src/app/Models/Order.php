<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
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
class Order extends Model
{
    public const StatusPending = 'pending';

    public const StatusProcessing = 'processing';

    public const StatusCompleted = 'completed';

    public const StatusCancelled = 'cancelled';

    public const PaymentStatusUnpaid = 'unpaid';

    public const PaymentStatusPaid = 'paid';

    public const PaymentStatusRefunded = 'refunded';

    protected $attributes = [
        'status' => self::StatusPending,
        'payment_status' => self::PaymentStatusUnpaid,
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $query) use ($search): void {
            $query->where('order_number', 'like', "%{$search}%")
                ->orWhere('recipient_name', 'like', "%{$search}%")
                ->orWhere('phone_number', 'like', "%{$search}%")
                ->orWhereHas('agent', function (Builder $query) use ($search): void {
                    $query->where('agt_name', 'like', "%{$search}%")
                        ->orWhere('login_id', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('items', function (Builder $query) use ($search): void {
                    $query->where('product_code', 'like', "%{$search}%")
                        ->orWhere('product_name', 'like', "%{$search}%");
                });
        });
    }

    /**
     * @return Collection<int, array{product_name: string, required: int, available: int}>
     */
    public function stockShortages(): Collection
    {
        return $this->items
            ->map(function (OrderItem $item): ?array {
                $required = $item->missingReservationQuantity();
                $available = max(0, (int) $item->product?->prd_balance);

                if ($required <= $available) {
                    return null;
                }

                return [
                    'product_name' => $item->product_name,
                    'required' => $required,
                    'available' => $available,
                ];
            })
            ->filter()
            ->values();
    }

    public function statusLabel(): string
    {
        return Str::headline($this->status);
    }

    public function paymentStatusLabel(): string
    {
        return Str::headline($this->payment_status);
    }

    public function paymentMethodLabel(): string
    {
        return $this->payment_method === 'pay_later' ? 'Pay later' : 'Bank transfer';
    }

    public function grossSubtotalAmount(): float
    {
        return round($this->items->sum(function (OrderItem $item): float {
            return (float) $item->unit_selling_price * (int) $item->quantity;
        }), 2);
    }

    public function discountAmount(): float
    {
        return round(max(0, $this->grossSubtotalAmount() - (float) $this->subtotal), 2);
    }

    public function effectiveDiscountPercentage(): float
    {
        $grossSubtotal = $this->grossSubtotalAmount();

        if ($grossSubtotal <= 0) {
            return 0.0;
        }

        return round(($this->discountAmount() / $grossSubtotal) * 100, 1);
    }

    public function fulfilmentLabel(): string
    {
        return $this->fulfilment_method === 'delivery' ? 'Delivery' : 'Self pickup';
    }

    public function deliveryFeeAmount(): float
    {
        return round((float) ($this->delivery_fee ?? 0), 2);
    }

    public function deliveryFeeLabel(): string
    {
        return $this->delivery_fee === null
            ? 'No delivery charge'
            : 'RM '.number_format((float) $this->delivery_fee, 2);
    }

    /** @return array<int, string> */
    public function paymentProofPaths(): array
    {
        $paths = is_array($this->payment_proof_paths) ? $this->payment_proof_paths : [];

        return array_values(array_filter($paths, fn ($path) => is_string($path) && $path !== ''));
    }

    /** @return array<int, string> */
    public function paymentProofUrls(): array
    {
        return array_map(fn (string $path): string => $this->pictureUrl($path), $this->paymentProofPaths());
    }

    public function pictureUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (File::exists(public_path($path))) {
            return asset($path);
        }

        return Storage::disk($this->pictureDisk())->url($path);
    }

    private function pictureDisk(): string
    {
        $default = (string) config('filesystems.default', 'public');

        if ($default === 's3' && class_exists('League\\Flysystem\\AwsS3V3\\PortableVisibilityConverter')) {
            return 's3';
        }

        return 'public';
    }

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'payment_proof_paths' => 'array',
            'total_units' => 'integer',
            'placed_at' => 'datetime',
            'admin_notification_sent_at' => 'datetime',
            'agent_submission_email_sent_at' => 'datetime',
            'inventory_reserved_at' => 'datetime',
            'processed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
