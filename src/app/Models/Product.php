<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'prd_code',
    'prd_name',
    'weight_g',
    'width_mm',
    'height_mm',
    'prd_balance',
    'cost_rm',
    'price_selling',
    'agent_discount_default',
    'prd_picture',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;
}
