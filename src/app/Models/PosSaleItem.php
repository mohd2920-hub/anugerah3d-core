<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pos_sale_id',
    'product_id',
    'product_code',
    'product_name',
    'quantity',
    'unit_price',
    'agent_discount_percentage',
    'agent_discount_amount',
    'customer_discount_amount',
    'line_total',
])]
class PosSaleItem extends Model
{
    public function posSale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'agent_discount_percentage' => 'decimal:2',
            'agent_discount_amount' => 'decimal:2',
            'customer_discount_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }
}
