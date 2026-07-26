<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'weekly_closing_id',
    'agent_id',
    'agent_name',
    'agent_email',
    'agent_bank_name',
    'agent_bank_account_name',
    'agent_bank_account_number',
    'referrer_name',
    'referrer_email',
    'referrer_phone',
    'tier1_rate',
    'tier2_rate',
    'personal_orders_count',
    'personal_order_amount',
    'new_agents_registered',
    'tier1_agents_total',
    'tier2_agents_total',
    'tier1_orders_count',
    'tier2_orders_count',
    'tier1_orders_amount',
    'tier2_orders_amount',
    'tier1_bonus',
    'tier2_bonus',
    'total_bonus',
    'pos_sales_count',
    'pos_sales_amount',
    'payout_status',
    'paid_at',
    'notified_agent_at',
    'paid_by_admin_id',
    'payment_reference',
    'payment_receipt_datetime_text',
    'payment_attachment_path',
    'payment_notes',
])]
class WeeklyClosingAgentSummary extends Model
{
    public function closing(): BelongsTo
    {
        return $this->belongsTo(WeeklyClosing::class, 'weekly_closing_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function paidByAdmin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'paid_by_admin_id');
    }

    protected function casts(): array
    {
        return [
            'tier1_rate' => 'decimal:2',
            'tier2_rate' => 'decimal:2',
            'personal_order_amount' => 'decimal:2',
            'tier1_orders_amount' => 'decimal:2',
            'tier2_orders_amount' => 'decimal:2',
            'tier1_bonus' => 'decimal:2',
            'tier2_bonus' => 'decimal:2',
            'total_bonus' => 'decimal:2',
            'pos_sales_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'notified_agent_at' => 'datetime',
        ];
    }
}
