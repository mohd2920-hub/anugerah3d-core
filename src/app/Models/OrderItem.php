<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_id',
    'product_code',
    'product_name',
    'quantity',
    'reserved_quantity',
    'unit_selling_price',
    'discount_percentage',
    'unit_price',
    'line_total',
    'is_preorder',
])]
class OrderItem extends Model
{
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function missingReservationQuantity(): int
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'unit_selling_price' => 'decimal:2',
            'discount_percentage' => 'decimal:1',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'is_preorder' => 'boolean',
        ];
    }
}
