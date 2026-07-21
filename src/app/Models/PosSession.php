<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['agent_id', 'business_site_id', 'signed_in_at', 'expires_at', 'signed_out_at'])]
class PosSession extends Model
{
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function businessSite(): BelongsTo
    {
        return $this->belongsTo(BusinessSite::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(PosSale::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('signed_out_at')
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()));
    }

    protected function casts(): array
    {
        return [
            'signed_in_at' => 'datetime',
            'expires_at' => 'datetime',
            'signed_out_at' => 'datetime',
        ];
    }
}
