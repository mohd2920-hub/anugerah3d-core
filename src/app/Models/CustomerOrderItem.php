<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_id',
    'product_code',
    'product_name',
    'quantity',
    'clicker_character_count',
    'clicker_characters',
    'clicker_casing_image_path',
    'clicker_casing_image_id',
    'clicker_huruf_image_path',
    'reserved_quantity',
    'unit_selling_price',
    'discount_percentage',
    'unit_price',
    'line_total',
    'is_preorder',
])]
class CustomerOrderItem extends OrderItem
{
    protected $table = 'customer_order_items';

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'order_id');
    }
}
