<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

#[Fillable([
    'prd_code',
    'prd_name',
    'product_type',
    'weight_g',
    'width_mm',
    'height_mm',
    'length_mm',
    'prd_balance',
    'cost_rm',
    'price_selling',
    'agent_discount_default',
    'is_visible_to_agents',
    'color',
    'material',
    'material_id',
    'prd_picture',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'is_visible_to_agents' => true,
    ];

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $query) use ($search): void {
            $query->where('prd_code', 'like', "%{$search}%")
                ->orWhere('prd_name', 'like', "%{$search}%");
        });
    }

    public function scopeVisibleToAgents(Builder $query): Builder
    {
        return $query->where('is_visible_to_agents', true)->availableForSale();
    }

    public function discontinuationAvailable(): bool
    {
        return once(fn (): bool => Schema::hasColumn('products', 'discontinued_at') && Schema::hasColumn('pos_sale_items', 'uses_product_stock'));
    }

    public function scopeAvailableForSale(Builder $query): Builder
    {
        return $query->when($this->discontinuationAvailable(), fn (Builder $query) => $query->where(
            fn (Builder $query) => $query->whereNull('discontinued_at')->orWhere('prd_balance', '>', 0),
        ));
    }

    public function materialType()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    public function posSaleItems(): HasMany
    {
        return $this->hasMany(PosSaleItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight_g' => 'decimal:2',
            'width_mm' => 'decimal:2',
            'height_mm' => 'decimal:2',
            'length_mm' => 'decimal:2',
            'prd_balance' => 'integer',
            'cost_rm' => 'decimal:2',
            'price_selling' => 'decimal:2',
            'agent_discount_default' => 'decimal:2',
            'is_visible_to_agents' => 'boolean',
            'discontinued_at' => 'datetime',
        ];
    }
}
