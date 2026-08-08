<?php

namespace App\Actions\BusinessSites;

use App\Models\BusinessSite;
use App\Models\BusinessSiteOperation;
use Illuminate\Support\Facades\DB;

class StartBusinessSite
{
    public function handle(BusinessSite $businessSite): BusinessSite
    {
        return DB::transaction(function () use ($businessSite): BusinessSite {
            $lockedSite = BusinessSite::query()->lockForUpdate()->findOrFail($businessSite->getKey());
            $openedAt = $lockedSite->opened_at ?? now();

            if (! $lockedSite->isOpen()) {
                $lockedSite->update(['opened_at' => $openedAt]);
            }

            BusinessSiteOperation::query()->firstOrCreate([
                'business_site_id' => $lockedSite->getKey(),
                'closed_at' => null,
            ], [
                'opened_at' => $openedAt,
            ]);

            return $lockedSite;
        });
    }
}
