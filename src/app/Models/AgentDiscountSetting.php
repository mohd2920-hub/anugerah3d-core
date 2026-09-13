<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

#[Fillable(['below_rm20', 'below_rm100', 'at_least_rm100', 'version'])]
class AgentDiscountSetting extends Model
{
    protected $attributes = ['below_rm20' => 10, 'below_rm100' => 25, 'at_least_rm100' => 25, 'version' => 0];

    public static function current(): self
    {
        return Schema::hasTable('agent_discount_settings') ? (static::query()->find(1) ?? new self) : new self;
    }

    protected function casts(): array
    {
        return ['below_rm20' => 'float', 'below_rm100' => 'float', 'at_least_rm100' => 'float', 'version' => 'integer'];
    }
}
