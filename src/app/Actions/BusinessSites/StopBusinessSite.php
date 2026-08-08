<?php

namespace App\Actions\BusinessSites;

use App\Models\BusinessSite;
use App\Models\BusinessSiteOperation;
use Illuminate\Support\Facades\DB;

class StopBusinessSite
{
    public function handle(BusinessSite $businessSite): int
    {
        return DB::transaction(function () use ($businessSite): int {
            $lockedSite = BusinessSite::query()->lockForUpdate()->findOrFail($businessSite->getKey());

            if (! $lockedSite->isOpen()) {
                return 0;
            }

            $stoppedAt = now();
            $operation = BusinessSiteOperation::query()->firstOrCreate([
                'business_site_id' => $lockedSite->getKey(),
                'closed_at' => null,
            ], [
                'opened_at' => $lockedSite->opened_at,
            ]);
            $operation->update(['closed_at' => $stoppedAt]);
            $lockedSite->update(['opened_at' => null]);

            return $lockedSite->posSessions()
                ->whereNull('signed_out_at')
                ->update(['signed_out_at' => $stoppedAt]);
        });
    }
}
