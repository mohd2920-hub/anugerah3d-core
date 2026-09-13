<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pos_sale_id', 'admin_user_id', 'request_token', 'action', 'reason', 'before', 'after'])]
class PosSaleCorrection extends Model
{
    protected static function booted(): void
    {
        static::updating(function (self $correction): void {
            throw new \LogicException('Correction history cannot be changed.');
        });
        static::deleting(function (self $correction): void {
            throw new \LogicException('Correction history cannot be deleted.');
        });
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }

    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array'];
    }
}
