<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['site_name', 'city', 'opened_at'])]
class BusinessSite extends Model
{
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotNull('opened_at');
    }

    public function isOpen(): bool
    {
        return $this->opened_at !== null;
    }

    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(Agent::class)->withTimestamps();
    }

    public function posSessions(): HasMany
    {
        return $this->hasMany(PosSession::class);
    }

    public function posSales(): HasMany
    {
        return $this->hasMany(PosSale::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(BusinessSiteOperation::class);
    }

    public function activePosSessions(): HasMany
    {
        return $this->posSessions()->active();
    }

    public function currentOperationPosSessions(): HasMany
    {
        $sitesTable = $this->getTable();
        $sessionsTable = (new PosSession)->getTable();

        return $this->posSessions()
            ->whereHas('businessSite', fn (Builder $query): Builder => $query
                ->open()
                ->whereColumn("{$sitesTable}.opened_at", '<=', "{$sessionsTable}.signed_in_at"))
            ->latest('signed_in_at');
    }

    public function todayPosSessions(): HasMany
    {
        return $this->posSessions()
            ->whereBetween('signed_in_at', [now()->startOfDay(), now()->endOfDay()])
            ->latest('signed_in_at');
    }

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
        ];
    }
}
