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
    'clicker_character_count',
    'clicker_characters',
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

    public function isClicker(): bool
    {
        return (int) ($this->clicker_character_count ?? 0) > 0;
    }

    public function clickerCharactersText(): ?string
    {
        if (! is_array($this->clicker_characters) || $this->clicker_characters === []) {
            return null;
        }

        $characters = collect($this->clicker_characters)
            ->map(fn (mixed $character): string => strtoupper(trim((string) $character)))
            ->filter()
            ->values();

        if ($characters->isEmpty()) {
            return null;
        }

        return $characters->implode('');
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'clicker_character_count' => 'integer',
            'clicker_characters' => 'array',
            'reserved_quantity' => 'integer',
            'unit_selling_price' => 'decimal:2',
            'discount_percentage' => 'decimal:1',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'is_preorder' => 'boolean',
        ];
    }
}
