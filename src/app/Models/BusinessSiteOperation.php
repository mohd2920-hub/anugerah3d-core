<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['business_site_id', 'opened_at', 'closed_at', 'report_date'])]
class BusinessSiteOperation extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (self $record): void {
            throw new \LogicException('Historical records cannot be deleted.');
        });
    }

    public function businessSite(): BelongsTo
    {
        return $this->belongsTo(BusinessSite::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(PosSale::class);
    }

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'report_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }
}
