<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryPayment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['email_sent_at' => 'datetime', 'work_date' => 'date', 'paid_date' => 'date', 'amount_cents' => 'integer', 'overlap_details' => 'array'];
    }

    public function slipNumber(): string
    {
        return 'SAL-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
}
