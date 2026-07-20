<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['site_name', 'city'])]
class BusinessSite extends Model
{
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
}
