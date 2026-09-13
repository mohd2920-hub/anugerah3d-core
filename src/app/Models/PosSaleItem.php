<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'pos_sale_id',
    'product_id',
    'product_code',
    'product_name',
    'clicker_configuration',
    'stock_casing_image_id',
    'uses_product_stock',
    'quantity',
    'unit_price',
    'unit_cost',
    'agent_discount_percentage',
    'agent_discount_amount',
    'customer_discount_amount',
    'line_total',
])]
class PosSaleItem extends Model
{
    use SoftDeletes;

    public function posSale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function correctionInput(): array
    {
        $config = $this->clicker_configuration ?? null;
        $input = ['sale_item_id' => $this->id, 'product_id' => $this->product_id, 'quantity' => $this->quantity, 'discount_amount' => $this->customer_discount_amount];
        if ($config) {
            $input += ['clicker_casing_image_id' => $config['casing_id'], 'clicker_huruf_image_id' => $config['huruf_id'], 'clicker_character_count' => $config['character_count'], 'clicker_characters' => $config['characters']];
        }

        return $input;
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'uses_product_stock' => 'boolean',
            'clicker_configuration' => 'array',
            'stock_casing_image_id' => 'integer',
            'unit_price' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'agent_discount_percentage' => 'decimal:2',
            'agent_discount_amount' => 'decimal:2',
            'customer_discount_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }
}
