<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'week_key',
    'period_start',
    'period_end',
    'status',
    'closed_at',
    'email_dispatched_at',
    'backup_path',
    'total_agents',
    'total_orders',
    'total_order_amount',
    'total_order_units',
    'total_pos_sales',
    'total_pos_amount',
    'total_new_agents',
    'total_tier1_orders',
    'total_tier2_orders',
    'total_payable_bonus',
])]
class WeeklyClosing extends Model
{
    public function agentSummaries(): HasMany
    {
        return $this->hasMany(WeeklyClosingAgentSummary::class);
    }

    protected function casts(): array
    {
        return [
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'closed_at' => 'datetime',
            'email_dispatched_at' => 'datetime',
            'total_order_amount' => 'decimal:2',
            'total_pos_amount' => 'decimal:2',
            'total_payable_bonus' => 'decimal:2',
        ];
    }
}
