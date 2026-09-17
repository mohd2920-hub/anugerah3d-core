<?php

namespace App\Actions\BusinessSites;

use App\Models\AdminUser;
use App\Models\BusinessSite;
use App\Models\BusinessSiteOperation;
use App\Models\PosSale;
use App\Models\PosSession;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CorrectOperationClosure
{
    /** @param array{closed_at: string, reason: string, next_opened_at?: ?string, next_report_date?: ?string} $data */
    public function review(BusinessSiteOperation $operation, array $data): array
    {
        $end = Carbon::parse($data['closed_at']);
        $originalEnd = $operation->closed_at ?? now();
        if ($end->lte($operation->opened_at) || $end->gt($originalEnd)) {
            throw ValidationException::withMessages(['closed_at' => 'Masa tutup mesti selepas masa buka dan tidak melebihi masa tutup asal.']);
        }
        if (DB::table('staff_salary_drafts')->where('business_site_operation_id', $operation->id)->exists()) {
            throw ValidationException::withMessages(['closed_at' => 'Sesi ini mempunyai rekod draf / bayaran gaji. Selesaikan semakan gaji sebelum membetulkan sesi.']);
        }
        $sales = PosSale::query()->where('business_site_operation_id', $operation->id)->orderBy('id')->get();
        $lateSales = $sales->filter(fn (PosSale $sale): bool => $sale->sold_at->gt($end));
        $attendances = PosSession::query()->with('agent:id,agt_name')->where('business_site_id', $operation->business_site_id)
            ->where('signed_in_at', '<=', $originalEnd)->where(fn (Builder $q): Builder => $q->whereNull('signed_out_at')->orWhere('signed_out_at', '>', $end))
            ->orderBy('id')->get();
        $next = empty($data['next_opened_at']) ? null : Carbon::parse($data['next_opened_at']);
        if (($lateSales->isNotEmpty() || $attendances->isNotEmpty()) && ! $next) {
            throw ValidationException::withMessages(['next_opened_at' => 'Ada jualan / kehadiran selepas masa tutup. Tetapkan sesi pengganti untuk rekod ini.']);
        }
        if ($next && $next->gt($originalEnd)) {
            throw ValidationException::withMessages(['next_opened_at' => 'Masa buka sesi pengganti tidak boleh selepas masa tutup asal.']);
        }
        $uncoveredSales = $next ? $lateSales->filter(fn (PosSale $sale): bool => $sale->sold_at->lt($next))->sortBy('sold_at') : collect();
        if ($uncoveredSales->isNotEmpty()) {
            $firstSale = $uncoveredSales->first();
            $messages = ['Ada jualan antara masa tutup sebenar dan masa buka sesi pengganti. Rekod berikut belum termasuk dalam mana-mana sesi:'];
            foreach ($uncoveredSales as $sale) {
                $messages[] = $sale->sale_number.' — '.$sale->sold_at->format('d/m/Y H:i:s');
            }
            $messages[] = 'Semak waktu operasi sebenar. Untuk meliputi rekod ini, sesi pengganti perlu dibuka selewat-lewatnya '.$firstSale->sold_at->format('d/m/Y H:i:s').'.';
            throw ValidationException::withMessages(['next_opened_at' => $messages]);
        }
        if ($next && $attendances->contains(fn (PosSession $session): bool => $session->signed_in_at->gt($end) && $session->signed_in_at->lt($next))) {
            throw ValidationException::withMessages(['next_opened_at' => 'Masa buka sesi pengganti mesti meliputi kehadiran selepas masa tutup.']);
        }
        if ($next && BusinessSiteOperation::query()->where('business_site_id', $operation->business_site_id)->where('id', '!=', $operation->id)
            ->where('opened_at', '<=', $originalEnd)->where(fn (Builder $q): Builder => $q->whereNull('closed_at')->orWhere('closed_at', '>=', $next))->exists()) {
            throw ValidationException::withMessages(['next_opened_at' => 'Tempoh sesi pengganti bertindih dengan sesi sedia ada.']);
        }
        $snapshot = ['operation' => $operation->getAttributes(), 'sales' => $sales->map->getAttributes()->all(), 'attendances' => $attendances->map->getAttributes()->all(), 'site' => $operation->loadMissing('businessSite')->businessSite->getAttributes()];

        return ['snapshot' => $snapshot, 'fingerprint' => hash('sha256', json_encode($snapshot)), 'late_sales' => $lateSales->values(), 'attendances' => $attendances];
    }

    /** @param array{closed_at: string, reason: string, next_opened_at?: ?string, next_report_date?: ?string} $data */
    public function apply(BusinessSiteOperation $operation, AdminUser $admin, array $data, string $fingerprint): void
    {
        abort_unless($admin->isSuperAdmin(), 403);
        DB::transaction(function () use ($operation, $admin, $data, $fingerprint): void {
            $site = BusinessSite::query()->lockForUpdate()->findOrFail($operation->business_site_id);
            $operation = BusinessSiteOperation::query()->lockForUpdate()->findOrFail($operation->id);
            PosSale::query()->where('business_site_operation_id', $operation->id)->orderBy('id')->lockForUpdate()->get();
            PosSession::query()->where('business_site_id', $site->id)->orderBy('id')->lockForUpdate()->get();
            $review = $this->review($operation, $data);
            if (! hash_equals($fingerprint, $review['fingerprint'])) {
                throw ValidationException::withMessages(['closed_at' => 'Rekod telah berubah. Semak pembetulan sekali lagi.']);
            }
            $wasOpen = $operation->closed_at === null;
            $originalEnd = $operation->closed_at;
            $replacement = null;
            if (! empty($data['next_opened_at'])) {
                $replacement = BusinessSiteOperation::query()->create(['business_site_id' => $site->id, 'opened_at' => $data['next_opened_at'], 'closed_at' => $originalEnd, 'report_date' => $data['next_report_date']]);
                PosSale::query()->whereIn('id', $review['late_sales']->pluck('id'))->update(['business_site_operation_id' => $replacement->id, 'report_date' => $data['next_report_date']]);
            }
            $operation->update(['closed_at' => $data['closed_at']]);
            if ($wasOpen) {
                $site->update(['opened_at' => $replacement?->opened_at]);
            }
            DB::table('operation_closure_corrections')->insert(['business_site_operation_id' => $operation->id, 'replacement_operation_id' => $replacement?->id, 'admin_id' => $admin->id, 'reason' => $data['reason'], 'before_snapshot' => json_encode($review['snapshot']), 'after_snapshot' => json_encode(['operation' => $operation->getAttributes(), 'replacement' => $replacement?->getAttributes(), 'moved_sale_ids' => $review['late_sales']->pluck('id')->all()]), 'created_at' => now()]);
        }, 3);
    }
}
