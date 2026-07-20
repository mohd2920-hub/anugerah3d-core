<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'sale_number',
    'pos_session_id',
    'business_site_id',
    'recorded_by_agent_id',
    'sales_agent_id',
    'customer_name',
    'customer_phone',
    'remark',
    'payment_method',
    'payment_remark',
    'sale_picture_path',
    'payment_proof_path',
    'total_amount',
    'sold_at',
])]
class PosSale extends Model
{
    public const PaymentCash = 'cash';

    public const PaymentQr = 'qr';

    public static function paymentMethods(): array
    {
        return [
            self::PaymentCash => 'Cash',
            self::PaymentQr => 'QR',
        ];
    }

    public function posSession(): BelongsTo
    {
        return $this->belongsTo(PosSession::class);
    }

    public function businessSite(): BelongsTo
    {
        return $this->belongsTo(BusinessSite::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'recorded_by_agent_id');
    }

    public function salesAgent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'sales_agent_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosSaleItem::class);
    }

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'sold_at' => 'datetime',
        ];
    }
}
